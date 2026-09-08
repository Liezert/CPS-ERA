<?php

namespace App\Livewire\Ba;

use App\Models\BaActivityLog;
use App\Models\BaIncident;
use App\Services\BaIncidentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Berita Acara - CPS ERA')]
class Show extends Component
{
    public BaIncident $incident;

    public string $revisionNote = '';

    public bool $showRevisionModal = false;

    public function mount(BaIncident $incident): void
    {
        $this->incident = $incident->load(['division', 'creator', 'reviewer', 'activityLogs.actor', 'lessonLearned']);
    }

    /**
     * Otorisasi khusus: Tombol Approve/Reject HANYA muncul untuk
     * Supervisor dari divisi yang sama dengan BA tersebut.
     */
    public function getCanApproveProperty(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('supervisor')
            && (int) $user->division_id === (int) $this->incident->division_id
            && $this->incident->isCreated();
    }

    /**
     * Otorisasi tombol Tutup Laporan (Closed):
     * Supervisor dari divisi yang sama atau Quality/Admin saat status 'reviewed'.
     */
    public function getCanCloseProperty(): bool
    {
        $user = Auth::user();
        if (! $user || ! $this->incident->isReviewed()) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'quality'])) {
            return true;
        }

        return $user->hasRole('supervisor')
            && (int) $user->division_id === (int) $this->incident->division_id;
    }

    /**
     * Setujui BA dan ubah status menjadi 'reviewed'.
     * Menjalankan efek samping PRD 3.1: otomatis membuat entri Lesson Learned dari FTK.
     */
    public function approve(BaIncidentService $service): void
    {
        if (! $this->canApprove) {
            abort(403, 'Aksi persetujuan hanya diizinkan untuk Supervisor divisi terkait.');
        }

        $user = Auth::user();
        $this->incident = $service->review($this->incident, $user, 'BA telah diverifikasi dan disetujui oleh Supervisor Divisi.');

        session()->flash('status', 'Berita Acara berhasil disetujui. Materi Lesson Learned otomatis diterbitkan.');
        $this->incident->load(['division', 'creator', 'reviewer', 'activityLogs.actor', 'lessonLearned']);
    }

    /**
     * Ajukan catatan revisi / tolak sementara.
     */
    public function submitRevision(): void
    {
        if (! $this->canApprove) {
            abort(403, 'Aksi pengajuan revisi hanya diizinkan untuk Supervisor divisi terkait.');
        }

        $this->validate([
            'revisionNote' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'revisionNote.required' => 'Catatan revisi wajib dicantumkan.',
        ]);

        $user = Auth::user();

        BaActivityLog::create([
            'ba_incident_id' => $this->incident->id,
            'actor_id' => $user->id,
            'action' => 'Revisi diminta oleh '.$user->name,
            'note' => $this->revisionNote,
        ]);

        $this->showRevisionModal = false;
        $this->revisionNote = '';

        session()->flash('status', 'Catatan perbaikan telah dicatat ke timeline riwayat insiden.');
        $this->incident->load(['activityLogs.actor']);
    }

    /**
     * Tutup laporan BA (status -> 'closed').
     */
    public function closeIncident(BaIncidentService $service): void
    {
        if (! $this->canClose) {
            abort(403, 'Anda tidak memiliki hak akses untuk menutup laporan BA ini.');
        }

        $user = Auth::user();
        $this->incident = $service->close($this->incident, $user, 'Tindakan korektif selesai diverifikasi. Laporan BA resmi ditutup.');

        session()->flash('status', 'Laporan Berita Acara berhasil ditutup.');
        $this->incident->load(['division', 'creator', 'reviewer', 'activityLogs.actor', 'lessonLearned']);
    }

    public function render()
    {
        return view('livewire.ba.show');
    }
}
