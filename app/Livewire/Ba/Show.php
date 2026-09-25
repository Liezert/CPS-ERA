<?php

namespace App\Livewire\Ba;

use App\Enums\BaIncidentStatus;
use App\Models\BaIncident;
use App\Models\Division;
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

    public function mount(BaIncident $incident): void
    {
        Gate::authorize('view', $incident);

        $this->incident = $incident->load(['division', 'creator', 'reviewer', 'video', 'activityLogs.actor', 'learningMaterial']);
    }

    /**
     * Tautan ke halaman review Filament untuk reviewer, selama laporan menunggu review.
     * Approve/reject sendiri hanya tersedia di panel Filament (lewat policy per tahap).
     */
    public function getCanOpenReviewPanelProperty(): bool
    {
        $user = Auth::user();
        if (! $user || ! $user->hasAnyRole(['admin', 'quality', 'supervisor'])) {
            return false;
        }

        return in_array($this->incident->status, [
            BaIncidentStatus::PendingSupervisor->value,
            BaIncidentStatus::PendingHr->value,
        ], true) && $user->can('view', $this->incident);
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
