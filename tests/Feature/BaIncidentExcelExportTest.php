<?php

namespace Tests\Feature;

use App\Enums\BaIncidentStatus;
use App\Filament\Resources\BaIncidents\Pages\ListBaIncidents;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use App\Services\BaIncidentExcelExport;
use App\Services\BaIncidentService;
use Carbon\CarbonImmutable;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;
use ZipArchive;

class BaIncidentExcelExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $reporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $produksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->admin = User::factory()->create(['division_id' => $produksi->id]);
        $this->admin->assignRole('admin');
        $this->reporter = User::factory()->create(['name' => 'Rina Pelapor', 'division_id' => $produksi->id]);
        $this->reporter->assignRole('employee');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function report(string $tanggalPengisian, array $data = []): BaIncident
    {
        $incident = app(BaIncidentService::class)->saveDraft($data + [
            'division_id' => $this->reporter->division_id,
            'tanggal_pengisian' => $tanggalPengisian,
            'tanggal_masalah' => $tanggalPengisian,
            'sumber_ketidaksesuaian' => 'lain_lain',
            'sumber_ketidaksesuaian_lainnya' => 'Temuan patroli K3',
            'lokasi' => 'Lini Filling 03',
            'deskripsi_masalah' => 'Label kemasan tercetak miring.',
            'why_1' => 'Guide label bergeser.',
            'kesimpulan_akar_masalah' => 'Belum ada checklist ganti roll.',
            'koreksi_deskripsi' => 'Sortir ulang karton.',
            'korektif_deskripsi' => 'Tambah checklist ganti roll.',
            'korektif_waktu' => '2025-07-01',
            'is_potensi_risiko' => true,
        ], $this->reporter);

        $incident->update(['status' => BaIncidentStatus::PendingHr->value]);

        return $incident->fresh();
    }

    public function test_range_presets_follow_the_business_timezone(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-29 10:00:00', 'UTC'));

        $this->assertSame(['2025-01-01', '2025-12-31'], BaIncidentExcelExport::range('tahun_lalu'));
        $this->assertSame(['2026-08-01', '2026-08-31'], BaIncidentExcelExport::range('bulan_lalu'));
        $this->assertSame(['2025-09-29', '2026-09-29'], BaIncidentExcelExport::range('12_bulan'));
        $this->assertSame([null, null], BaIncidentExcelExport::range('semua'));
        $this->assertSame(['2026-01-05', '2026-02-10'], BaIncidentExcelExport::range('kustom', '2026-01-05', '2026-02-10'));

        // 30 Sep 18:00 UTC sudah 1 Okt di WIB: "bulan ini" = Oktober.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-30 18:00:00', 'UTC'));
        $this->assertSame(['2026-10-01', '2026-10-01'], BaIncidentExcelExport::range('bulan_ini'));
    }

    public function test_excel_follows_capa_form_columns_and_only_includes_the_chosen_range(): void
    {
        $inRange = $this->report('2025-06-10', ['deskripsi_masalah' => '=HYPERLINK("http://contoh.test","klik")']);
        $inRange->update(['potensi_kerugian' => true, 'nilai_kerugian' => 1500000, 'penanggung_kerugian' => [['nama' => 'Budi', 'nominal' => 1000000], ['nama' => 'Sari', 'nominal' => 500000]]]);
        \App\Models\BaActivityLog::create(['ba_incident_id' => $inRange->id, 'actor_id' => $this->admin->id, 'action' => 'BA Disetujui Supervisor', 'note' => 'Dicek di line 2.']);
        $this->report('2026-02-01');

        $export = app(BaIncidentExcelExport::class);
        [$from, $until] = ['2025-01-01', '2025-12-31'];
        $path = tempnam(sys_get_temp_dir(), 'capa').'.xlsx';
        $export->write($export->query($this->admin, $from, $until), $path);

        $reader = new Reader;
        $reader->open($path);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }
        $reader->close();

        [$header, $data] = [$rows[0], $rows[1]];
        $this->assertCount(2, $rows, 'Hanya laporan dengan Tanggal Pengisian di rentang yang ikut.');
        $this->assertSame(['No. FTK / Register BA', 'Tanggal Pengisian', 'Bagian / Divisi', 'Sumber Ketidaksesuaian', 'Tanggal Kejadian Masalah', 'Lokasi / Tempat Kejadian'], array_slice($header, 0, 6));
        $this->assertSame(['Potensi Risiko Signifikan', 'Potensi Peluang Improvement', 'Status', 'Pelapor'], array_slice($header, 19, 4));
        $this->assertSame(['Catatan Supervisor', 'Potensi Kerugian', 'Rekomendasi Ganti Rugi (Rp)', 'Ditanggung Oleh'], array_slice($header, -4));

        $cell = array_combine($header, $data);
        $this->assertSame($inRange->nomor_ba, $cell['No. FTK / Register BA']);
        $this->assertSame('2025-06-10', $cell['Tanggal Pengisian']->format('Y-m-d'));
        $this->assertSame('Produksi', $cell['Bagian / Divisi']);
        $this->assertSame('Lain-lain: Temuan patroli K3', $cell['Sumber Ketidaksesuaian']);
        $this->assertSame('2025-07-01', $cell['Target Tanggal Selesai']->format('Y-m-d'));
        $this->assertSame('Ya', $cell['Potensi Risiko Signifikan']);
        $this->assertSame('Tidak', $cell['Potensi Peluang Improvement']);
        $this->assertSame('Menunggu Review HR', $cell['Status']);
        $this->assertSame('Rina Pelapor', $cell['Pelapor']);
        $this->assertSame('Dicek di line 2.', $cell['Catatan Supervisor']);
        $this->assertSame('Ada', $cell['Potensi Kerugian']);
        $this->assertEquals(1500000, $cell['Rekomendasi Ganti Rugi (Rp)']);
        $this->assertSame("Budi: Rp 1.000.000\nSari: Rp 500.000", $cell['Ditanggung Oleh']);

        // Ketikan pengguna berawalan "=" harus tetap teks, bukan rumus Excel yang aktif.
        $this->assertSame('=HYPERLINK("http://contoh.test","klik")', $cell['Uraian Masalah / Ketidaksesuaian']);
        $zip = new ZipArchive;
        $zip->open($path);
        $this->assertStringNotContainsString('<f>', $zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();
        @unlink($path);
    }

    public function test_only_admin_can_download_and_empty_range_is_reported(): void
    {
        $this->report('2025-06-10');

        Livewire::actingAs($this->admin)
            ->test(ListBaIncidents::class)
            ->callAction('exportExcel', ['rentang' => 'semua'])
            ->assertFileDownloaded('laporan-capa_semua.xlsx');

        Livewire::actingAs($this->admin)
            ->test(ListBaIncidents::class)
            ->callAction('exportExcel', ['rentang' => 'kustom', 'dari' => '2020-01-01', 'sampai' => '2020-12-31'])
            ->assertNotified('Tidak ada laporan CAPA pada rentang waktu ini.');

        $quality = User::factory()->create(['division_id' => $this->reporter->division_id]);
        $quality->assignRole('quality');
        Livewire::actingAs($quality)
            ->test(ListBaIncidents::class)
            ->assertActionHidden('exportExcel');
    }
}
