<?php

namespace App\Livewire\Video;

use App\Models\Video;
use App\Policies\BaIncidentPolicy;
use App\Services\VideoApprovalService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar video kontribusi: antrean review untuk tim HR, dan riwayat unggahan milik sendiri.
 */
#[Layout('layouts.app')]
#[Title('Video Kontribusi - CPS ERA')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $user = Auth::user();

        $reviewQueue = $user->hasAnyRole(BaIncidentPolicy::HR_REVIEWER_ROLES)
            ? Video::with(['creator', 'division'])
                ->where('creation_reason', VideoApprovalService::CREATION_REASON)
                ->where('status', 'pending_hr')
                ->where('created_by', '!=', $user->id)
                ->oldest()
                ->get()
            : null;

        return view('livewire.video.index', [
            'reviewQueue' => $reviewQueue,
            'myVideos' => Video::where('created_by', $user->id)
                ->where('creation_reason', VideoApprovalService::CREATION_REASON)
                ->latest()
                ->paginate(10),
        ]);
    }
}
