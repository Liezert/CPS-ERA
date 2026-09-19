<?php

namespace App\Livewire\Ba;

use App\Models\BaIncident;
use App\Models\Division;
use App\Services\BaIncidentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Berita Acara & CAPA - CPS ERA')]
class Show extends Component
{
    public BaIncident $incident;

    // Verifikasi Tindakan Korektif (Approve Modal)
    public bool $showApproveModal = false;

    public string $statusVerifikasi = 'efektif'; // 'efektif' | 'tidak_efektif'

    public string $buktiObjektif = '';

    public string $alasanTidakEfektif = '';

    // Penolakan / Permintaan Revisi (Reject Modal)
    public bool $showRejectModal = false;

    public string $rejectionReason = '';

    public function mount(BaIncident $incident): void
    {
        $this->incident = $incident->load(['division', 'creator', 'reviewer', 'video', 'activityLogs.actor', 'lessonLearned']);
    }

    /**
     * Otorisasi Review: HANYA Admin ATAU Supervisor dari divisi yang sama.
     */
    public function getCanApproveProperty(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $isAuthorized = $user->hasRole('admin') ||
            ($user->hasRole('supervisor') && (int) $user->division_id === (int) $this->incident->division_id);

        return $isAuthorized && in_array($this->incident->status, ['submitted', 'created', 'draft'], true);
    }

    /**
     * Otorisasi Reject: Sama dengan otorisasi Review.
     */
    public function getCanRejectProperty(): bool
    {
        return $this->canApprove;
    }

    /**
     * Otorisasi Edit / Resubmit: Pembuat BA atau Admin, saat status 'rejected' atau 'draft'.
     */
    public function getCanEditProperty(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return ($user->id === $this->incident->created_by || $user->hasRole('admin'))
            && in_array($this->incident->status, ['rejected', 'draft'], true);
    }

    /**
     * Otorisasi tombol Tutup Laporan (Closed) legacy:
     */
    public function getCanCloseProperty(): bool
    {
        $user = Auth::user();
        if (! $user || ! $this->incident->isReviewed()) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'quality']) ||
            ($user->hasRole('supervisor') && (int) $user->division_id === (int) $this->incident->division_id);
    }

    public function openApproveModal(): void
    {
        $this->showApproveModal = true;
    }

    public function closeApproveModal(): void
    {
        $this->showApproveModal = false;
    }

    public function openRejectModal(): void
    {
        $this->showRejectModal = true;
    }

    public function closeRejectModal(): void
    {
        $this->showRejectModal = false;
    }

    /**
     * Setujui BA dengan verifikasi tindakan korektif lengkap.
     */
    public function confirmApprove(BaIncidentService $service): void
    {
        if (! $this->canApprove) {
            abort(403, 'Aksi persetujuan hanya diizinkan untuk Admin atau Supervisor divisi terkait.');
        }

        $rules = [
            'statusVerifikasi' => ['required', 'in:efektif,tidak_efektif'],
        ];

        if ($this->statusVerifikasi === 'efektif') {
            $rules['buktiObjektif'] = ['required', 'string', 'min:5'];
        } else {
            $rules['alasanTidakEfektif'] = ['required', 'string', 'min:5'];
        }

        $this->validate($rules, [
            'buktiObjektif.required' => 'Bukti objektif verifikasi wajib diisi untuk status efektif.',
            'alasanTidakEfektif.required' => 'Alasan ketidakefektifan wajib diisi untuk status tidak efektif.',
        ]);

        $user = Auth::user();
        $this->incident = $service->approve($this->incident, $user, [
            'status_verifikasi' => $this->statusVerifikasi,
            'bukti_objektif' => $this->buktiObjektif,
            'alasan_tidak_efektif' => $this->alasanTidakEfektif,
        ]);

        $this->showApproveModal = false;
        session()->flash('status', 'Berita Acara resmi disetujui. Verifikasi efektivitas tercatat dan materi Lesson Learned otomatis diterbitkan.');
        $this->incident->load(['division', 'creator', 'reviewer', 'video', 'activityLogs.actor', 'lessonLearned']);
    }

    /**
     * Alias method untuk aksi approve (misalnya dari pemanggilan langsung di test / button cepat).
     */
    public function approve(BaIncidentService $service): void
    {
        if (! $this->canApprove) {
            abort(403, 'Aksi persetujuan hanya diizinkan untuk Admin atau Supervisor divisi terkait.');
        }

        $user = Auth::user();
        $this->incident = $service->approve($this->incident, $user, [
            'status_verifikasi' => $this->statusVerifikasi ?: 'efektif',
            'bukti_objektif' => $this->buktiObjektif ?: 'Verifikasi tindakan korektif diverifikasi efektif.',
            'alasan_tidak_efektif' => $this->alasanTidakEfektif,
        ]);

        session()->flash('status', 'Berita Acara berhasil disetujui. Materi Lesson Learned otomatis diterbitkan.');
        $this->incident->load(['division', 'creator', 'reviewer', 'video', 'activityLogs.actor', 'lessonLearned']);
    }

    /**
     * Tolak BA dengan catatan penolakan.
     */
    public function confirmReject(BaIncidentService $service): void
    {
        if (! $this->canReject) {
            abort(403, 'Aksi penolakan hanya diizinkan untuk Admin atau Supervisor divisi terkait.');
        }

        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'rejectionReason.required' => 'Alasan penolakan / catatan revisi wajib diisi.',
        ]);

        $user = Auth::user();
        $this->incident = $service->reject($this->incident, $user, $this->rejectionReason);

        $this->showRejectModal = false;
        session()->flash('status', 'Laporan BA telah ditolak. Catatan perbaikan telah dicatat untuk direvisi oleh pembuat.');
        $this->incident->load(['division', 'creator', 'reviewer', 'video', 'activityLogs.actor', 'lessonLearned']);
    }

    /**
     * Backward-compatibility wrapper for legacy submitRevision.
     */
    public function submitRevision(BaIncidentService $service): void
    {
        $this->confirmReject($service);
    }

    /**
     * Tutup laporan BA (status -> 'closed' / 'approved').
     */
    public function closeIncident(BaIncidentService $service): void
    {
        if (! $this->canClose) {
            abort(403, 'Anda tidak memiliki hak akses untuk menutup laporan BA ini.');
        }

        $user = Auth::user();
        $this->incident = $service->close($this->incident, $user, 'Tindakan korektif selesai diverifikasi. Laporan BA resmi ditutup.');

        session()->flash('status', 'Laporan Berita Acara berhasil ditutup.');
        $this->incident->load(['division', 'creator', 'reviewer', 'video', 'activityLogs.actor', 'lessonLearned']);
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
