<?php

namespace App\Livewire\Ba;

use App\Models\BaIncident;
use App\Models\Division;
use App\Services\BaIncidentService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Berita Acara & CAPA - CPS ERA')]
class Show extends Component
{
    public BaIncident $incident;

    // Form review di halaman preview (Supervisor tahap 1, HR tahap 2).
    public string $catatanSupervisor = '';

    // Catatan potensi kerugian (Supervisor): 'ada' | 'tidak'; penanggung = [{nama, nominal}].
    public string $potensiKerugian = '';

    public ?string $nilaiKerugian = null;

    public array $penanggungKerugian = [['nama' => '', 'nominal' => null]];

    public string $statusVerifikasi = 'efektif';

    public string $buktiObjektif = '';

    public string $alasanTidakEfektif = '';

    public string $catatanPenolakan = '';

    public function mount(BaIncident $incident): void
    {
        Gate::authorize('view', $incident);

        $this->incident = $incident->load(['division', 'creator', 'reviewer', 'video', 'activityLogs.actor', 'learningMaterial']);
    }

    /**
     * Tahap review yang boleh dijalankan user ini: 'supervisor' (tahap 1), 'hr' (tahap 2), atau null.
     * Aturannya sepenuhnya dari policy (status laporan, divisi, four-eyes).
     */
    public function getReviewStageProperty(): ?string
    {
        $user = Auth::user();

        return match (true) {
            $user === null => null,
            $user->can('reviewAsSupervisor', $this->incident) => 'supervisor',
            $user->can('reviewAsHr', $this->incident) => 'hr',
            default => null,
        };
    }

    /**
     * Catatan potensi kerugian (nama penanggung + nominal) hanya untuk peninjau: Supervisor, HR, admin.
     * Pelapor dan employee tidak melihatnya, termasuk peninjau yang kebetulan pelapor laporan ini.
     */
    public function getCanSeeLossProperty(): bool
    {
        $user = Auth::user();

        return $user !== null
            && (int) $this->incident->created_by !== (int) $user->id
            && $user->hasAnyRole(['supervisor', 'quality', 'admin']);
    }

    /**
     * Catatan lapangan Supervisor (tahap 1), ditampilkan di ringkasan keputusan HR.
     */
    public function getSupervisorNoteProperty(): ?string
    {
        return app(BaIncidentService::class)->latestSupervisorNote($this->incident)?->note;
    }

    /**
     * Approve dari halaman preview: Supervisor meneruskan ke HR, HR menyetujui final
     * dengan evaluasi efektivitas tindakan korektif.
     */
    public function approve(BaIncidentService $service): void
    {
        $stage = $this->reviewStage;
        abort_unless($stage !== null, 403);

        if ($stage === 'hr') {
            $this->validate([
                'statusVerifikasi' => ['required', 'in:efektif,tidak_efektif'],
                'buktiObjektif' => ['nullable', 'string', 'max:2000', 'required_if:statusVerifikasi,efektif'],
                'alasanTidakEfektif' => ['nullable', 'string', 'max:2000', 'required_if:statusVerifikasi,tidak_efektif'],
            ], [
                'buktiObjektif.required_if' => 'Bukti objektif wajib diisi jika tindakan dinyatakan efektif.',
                'alasanTidakEfektif.required_if' => 'Alasan wajib diisi jika tindakan dinyatakan tidak efektif.',
            ]);
        } else {
            $loss = $this->validatedLoss();
            if ($loss === null) {
                return;
            }
        }

        try {
            $stage === 'hr'
                ? $service->approve($this->incident, Auth::user(), [
                    'status_verifikasi' => $this->statusVerifikasi,
                    'bukti_objektif' => $this->buktiObjektif,
                    'alasan_tidak_efektif' => $this->alasanTidakEfektif,
                ])
                : $service->approveAsSupervisor($this->incident, Auth::user(), trim($this->catatanSupervisor) ?: null, $loss);
        } catch (DomainException $exception) {
            $this->addError('review', $exception->getMessage());

            return;
        }

        session()->flash('status', $stage === 'hr'
            ? "Laporan {$this->incident->nomor_ba} disetujui final."
            : "Laporan {$this->incident->nomor_ba} disetujui dan diteruskan ke tim HR.");

        $this->redirect(route('ba.show', $this->incident), navigate: true);
    }

    /**
     * Validasi catatan Supervisor + potensi kerugian. Nominal diketik berformat rupiah ("1.500.000"),
     * jadi diubah ke angka dulu; total yang ditanggung harus sama dengan rekomendasi ganti rugi.
     *
     * @return array{potensi_kerugian: bool, nilai_kerugian: ?int, penanggung_kerugian: ?array}|null
     */
    private function validatedLoss(): ?array
    {
        $this->resetErrorBag();
        $ada = $this->potensiKerugian === 'ada';
        $data = [
            'catatanSupervisor' => $this->catatanSupervisor,
            'potensiKerugian' => $this->potensiKerugian,
            'nilaiKerugian' => self::rupiah($this->nilaiKerugian),
            'penanggungKerugian' => array_map(fn (array $row): array => [
                'nama' => trim((string) ($row['nama'] ?? '')),
                'nominal' => self::rupiah($row['nominal'] ?? null),
            ], array_values($this->penanggungKerugian)),
        ];
        $required = $ada ? 'required' : 'nullable';

        Validator::make($data, [
            'catatanSupervisor' => ['nullable', 'string', 'max:1000'],
            'potensiKerugian' => ['required', 'in:ada,tidak'],
            'nilaiKerugian' => [$required, 'integer', 'min:1'],
            'penanggungKerugian.*.nama' => [$required, 'string', 'max:150'],
            'penanggungKerugian.*.nominal' => [$required, 'integer', 'min:1'],
        ], [
            'potensiKerugian.required' => 'Pilih apakah ada potensi kerugian.',
            'nilaiKerugian.required' => 'Nilai rekomendasi ganti rugi wajib diisi.',
            'nilaiKerugian.min' => 'Nilai rekomendasi ganti rugi harus lebih dari Rp 0.',
            'penanggungKerugian.*.nama.required' => 'Nama penanggung wajib diisi.',
            'penanggungKerugian.*.nominal.required' => 'Nominal wajib diisi.',
            'penanggungKerugian.*.nominal.min' => 'Nominal harus lebih dari Rp 0.',
        ])->validate();

        if (! $ada) {
            return ['potensi_kerugian' => false, 'nilai_kerugian' => null, 'penanggung_kerugian' => null];
        }

        $total = array_sum(array_column($data['penanggungKerugian'], 'nominal'));
        if ($total !== $data['nilaiKerugian']) {
            $this->addError('penanggungKerugian', 'Total yang ditanggung (Rp '.number_format($total, 0, ',', '.').') harus sama dengan rekomendasi ganti rugi (Rp '.number_format($data['nilaiKerugian'], 0, ',', '.').').');

            return null;
        }

        return ['potensi_kerugian' => true, 'nilai_kerugian' => $data['nilaiKerugian'], 'penanggung_kerugian' => $data['penanggungKerugian']];
    }

    /** "Rp 1.500.000" / "1500000" -> 1500000; kosong -> null. */
    private static function rupiah(mixed $value): ?int
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits === '' ? null : (int) $digits;
    }

    public function addPenanggung(): void
    {
        $this->penanggungKerugian[] = ['nama' => '', 'nominal' => null];
    }

    public function removePenanggung(int $index): void
    {
        unset($this->penanggungKerugian[$index]);
        $this->penanggungKerugian = array_values($this->penanggungKerugian) ?: [['nama' => '', 'nominal' => null]];
    }

    /**
     * Tolak dari halaman preview: Supervisor mengembalikan ke pelapor untuk revisi,
     * HR menolak permanen. Alasan wajib diisi.
     */
    public function reject(BaIncidentService $service): void
    {
        $stage = $this->reviewStage;
        abort_unless($stage !== null, 403);

        $this->validate(
            ['catatanPenolakan' => ['required', 'string', 'min:5', 'max:2000']],
            [
                'catatanPenolakan.required' => 'Alasan penolakan wajib diisi.',
                'catatanPenolakan.min' => 'Alasan penolakan minimal 5 karakter.',
            ],
        );

        try {
            $service->reject($this->incident, Auth::user(), $this->catatanPenolakan);
        } catch (DomainException $exception) {
            $this->addError('review', $exception->getMessage());

            return;
        }

        session()->flash('status', $stage === 'hr'
            ? "Laporan {$this->incident->nomor_ba} ditolak permanen."
            : "Laporan {$this->incident->nomor_ba} dikembalikan ke pelapor untuk direvisi.");

        $this->redirect(route('ba.show', $this->incident), navigate: true);
    }

    public function getCanViewActivityLogProperty(): bool
    {
        return (bool) Auth::user()?->can('viewActivityLog', $this->incident);
    }

    /**
     * Otorisasi Edit / Resubmit: Pembuat BA atau Admin, saat laporan masih draf atau diminta revisi.
     */
    public function getCanEditProperty(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return ($user->id === $this->incident->created_by || $user->hasRole('admin'))
            && $this->incident->isEditable();
    }

    public function render()
    {
        return view('livewire.ba.show', [
            // Section 1-6 dirender dengan komponen yang sama dengan form employee (mode readonly).
            'capa' => $this->incident->capaFormValues(),
            'divisions' => Division::orderBy('id')->get(),
        ]);
    }
}
