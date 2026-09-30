<?php

namespace App\Services;

use App\Enums\BaIncidentStatus;
use App\Models\BaIncident;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\DateTimeCell;
use OpenSpout\Common\Entity\Cell\EmptyCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Ekspor laporan CAPA ke Excel (.xlsx). Kolom mengikuti urutan formulir CAPA/FTK
 * (bagian 1 s/d 6), ditambah Status dan Pelapor di akhir sebagai konteks review.
 * Rentang waktu dihitung dari Tanggal Pengisian, di zona waktu bisnis (config kpi.timezone).
 */
class BaIncidentExcelExport
{
    /** Judul kolom (urutan = urutan formulir CAPA/FTK) => lebar kolom di Excel. */
    private const COLUMNS = [
        'No. FTK / Register BA' => 18,
        'Tanggal Pengisian' => 14,
        'Bagian / Divisi' => 20,
        'Sumber Ketidaksesuaian' => 26,
        'Tanggal Kejadian Masalah' => 14,
        'Lokasi / Tempat Kejadian' => 28,
        'Uraian Masalah / Ketidaksesuaian' => 50,
        'Why 1' => 36,
        'Why 2' => 36,
        'Why 3' => 36,
        'Why 4' => 36,
        'Why 5' => 36,
        'Kesimpulan Akar Masalah (Root Cause)' => 45,
        'Deskripsi Tindakan Koreksi (Sementara)' => 45,
        'PIC Pelaksana (Operator/SPV)' => 22,
        'Batas Waktu Pelaksanaan' => 18,
        'Deskripsi Tindakan Korektif (Akar Masalah)' => 45,
        'PIC Penanggung Jawab' => 22,
        'Target Tanggal Selesai' => 14,
        'Potensi Risiko Signifikan' => 14,
        'Potensi Peluang Improvement' => 14,
        'Status' => 26,
        'Pelapor' => 24,
    ];

    /**
     * Pilihan rentang waktu untuk form ekspor.
     *
     * @return array<string, string>
     */
    public static function rangeOptions(): array
    {
        $year = CarbonImmutable::now(config('kpi.timezone'))->year;

        return [
            'bulan_ini' => 'Bulan ini',
            'bulan_lalu' => 'Bulan lalu',
            '3_bulan' => '3 bulan terakhir',
            '6_bulan' => '6 bulan terakhir',
            '12_bulan' => '12 bulan terakhir',
            'tahun_ini' => "Tahun ini ({$year})",
            'tahun_lalu' => 'Setahun sebelumnya ('.($year - 1).')',
            'semua' => 'Semua data',
            'kustom' => 'Pilih tanggal sendiri',
        ];
    }

    /**
     * Rentang Tanggal Pengisian (inklusif, format Y-m-d) untuk sebuah pilihan; null = tanpa batas.
     *
     * @return array{0: ?string, 1: ?string}
     */
    public static function range(string $preset, ?string $from = null, ?string $until = null): array
    {
        $today = CarbonImmutable::now(config('kpi.timezone'))->startOfDay();
        $lastMonth = $today->subMonthNoOverflow();
        $lastYear = $today->subYear();

        [$start, $end] = match ($preset) {
            'bulan_ini' => [$today->startOfMonth(), $today],
            'bulan_lalu' => [$lastMonth->startOfMonth(), $lastMonth->endOfMonth()],
            '3_bulan' => [$today->subMonthsNoOverflow(3), $today],
            '6_bulan' => [$today->subMonthsNoOverflow(6), $today],
            '12_bulan' => [$today->subYear(), $today],
            'tahun_ini' => [$today->startOfYear(), $today],
            'tahun_lalu' => [$lastYear->startOfYear(), $lastYear->endOfYear()],
            'kustom' => [$from ? CarbonImmutable::parse($from) : null, $until ? CarbonImmutable::parse($until) : null],
            default => [null, null],
        };

        return [$start?->toDateString(), $end?->toDateString()];
    }

    public static function filename(?string $from, ?string $until): string
    {
        return 'laporan-capa_'.($from || $until ? ($from ?? 'awal').'_sd_'.($until ?? 'sekarang') : 'semua').'.xlsx';
    }

    /**
     * Laporan yang boleh dilihat user pada rentang Tanggal Pengisian, urut tanggal lalu nomor.
     */
    public function query(User $user, ?string $from, ?string $until): Builder
    {
        return BaIncident::visibleTo($user)
            ->with(['division', 'creator'])
            ->when($from, fn (Builder $query) => $query->whereDate('tanggal_pengisian', '>=', $from))
            ->when($until, fn (Builder $query) => $query->whereDate('tanggal_pengisian', '<=', $until))
            ->orderBy('tanggal_pengisian')
            ->orderBy('nomor_ba');
    }

    /**
     * Tulis file .xlsx ke $path (termasuk 'php://output' untuk unduhan langsung).
     */
    public function write(Builder $query, string $path): void
    {
        $options = new Options;
        foreach (array_values(self::COLUMNS) as $index => $width) {
            $options->setColumnWidth($width, $index + 1);
        }

        $writer = new Writer($options);
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('Laporan CAPA')->setSheetView((new SheetView)->setFreezeRow(2));

        $wrapTop = (new Style)->setShouldWrapText()->setCellVerticalAlignment(CellVerticalAlignment::TOP);
        $dateStyle = (new Style)->setFormat('dd/mm/yyyy');

        $writer->addRow(new Row(
            array_map(fn (string $title): Cell => new StringCell($title, null), array_keys(self::COLUMNS)),
            (clone $wrapTop)->setFontBold(),
        ));

        foreach ($query->lazy(200) as $incident) {
            $writer->addRow(new Row(
                array_map(fn (DateTimeInterface|string|null $value): Cell => match (true) {
                    $value instanceof DateTimeInterface => new DateTimeCell($value, $dateStyle),
                    blank($value) => new EmptyCell(null, null),
                    // Selalu StringCell: Cell::fromValue() mengubah teks berawalan "=" menjadi rumus Excel,
                    // padahal isinya ketikan pengguna (celah injeksi rumus).
                    default => new StringCell($value, null),
                }, $this->row($incident)),
                $wrapTop,
            ));
        }

        $writer->close();
    }

    /**
     * Satu laporan sebagai baris Excel, urutan sama dengan COLUMNS.
     *
     * @return list<DateTimeInterface|string|null>
     */
    private function row(BaIncident $incident): array
    {
        $sumber = BaIncident::SUMBER_OPTIONS[$incident->sumber_ketidaksesuaian] ?? $incident->sumber_ketidaksesuaian;
        if ($incident->sumber_ketidaksesuaian === 'lain_lain' && filled($incident->sumber_ketidaksesuaian_lainnya)) {
            $sumber .= ': '.$incident->sumber_ketidaksesuaian_lainnya;
        }

        // Target tanggal selesai berupa tanggal (Y-m-d) sejak form memakai pemilih tanggal; data lama bisa teks bebas.
        $target = (string) $incident->korektif_waktu;
        $target = preg_match('/^\d{4}-\d{2}-\d{2}$/', $target) ? CarbonImmutable::parse($target) : $target;

        return [
            $incident->nomor_ba,
            $incident->tanggal_pengisian,
            $incident->division?->name,
            $sumber,
            $incident->tanggal_masalah,
            $incident->lokasi,
            $incident->deskripsi_masalah,
            $incident->why_1,
            $incident->why_2,
            $incident->why_3,
            $incident->why_4,
            $incident->why_5,
            $incident->kesimpulan_akar_masalah,
            $incident->koreksi_deskripsi,
            $incident->koreksi_pic,
            $incident->koreksi_waktu,
            $incident->korektif_deskripsi,
            $incident->korektif_pic,
            $target,
            $incident->is_potensi_risiko ? 'Ya' : 'Tidak',
            $incident->is_potensi_peluang ? 'Ya' : 'Tidak',
            BaIncidentStatus::tryFrom($incident->status)?->getLabel() ?? $incident->status,
            $incident->creator?->name,
        ];
    }
}
