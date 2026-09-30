<?php

namespace App\Livewire\Ba;

use App\Models\BaIncident;
use App\Models\Division;
use App\Services\BaIncidentService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
            $this->validate(['catatanSupervisor' => ['nullable', 'string', 'max:1000']]);
        }

        try {
            $stage === 'hr'
                ? $service->approve($this->incident, Auth::user(), [
                    'status_verifikasi' => $this->statusVerifikasi,
                    'bukti_objektif' => $this->buktiObjektif,
                    'alasan_tidak_efektif' => $this->alasanTidakEfektif,
                ])
                : $service->approveAsSupervisor($this->incident, Auth::user(), trim($this->catatanSupervisor) ?: null);
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
