<?php

namespace App\Livewire\Mission;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Mission & Game - CPS ERA')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $selectedType = 'all';

    #[Url(as: 'status')]
    public string $selectedStatus = 'all';

    #[Url(as: 'view')]
    public string $viewMode = 'grid';

    /**
     * Reset pagination saat pencarian atau filter berubah.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedType(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Switch antara tampilan kartu grid dan list tabel.
     */
    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['grid', 'list'])) {
            $this->viewMode = $mode;
        }
    }

    /**
     * Reset semua filter ke kondisi awal.
     */
    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedType', 'selectedStatus']);
        $this->resetPage();
    }

    public function render(): View
    {
        $userId = Auth::id();

        $query = Quiz::missions()
            ->with(['questions'])
            ->with(['currentUserAttempt'])
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($this->selectedType !== 'all', function ($q) {
                $q->where('type', $this->selectedType);
            })
            ->when($this->selectedStatus !== 'all' && $userId, function ($q) use ($userId) {
                if ($this->selectedStatus === 'completed') {
                    $q->whereHas('attempts', function ($a) use ($userId) {
                        $a->where('user_id', $userId)->where('passed', true);
                    });
                } elseif ($this->selectedStatus === 'uncompleted') {
                    $q->whereDoesntHave('attempts', function ($a) use ($userId) {
                        $a->where('user_id', $userId)->where('passed', true);
                    });
                }
            })
            ->orderBy('created_at', 'desc');

        $missions = $query->paginate(9);

        // Ringkasan metrik statistik misi
        $totalMissions = Quiz::missions()->count();
        $completedMissionsCount = $userId
            ? QuizAttempt::where('user_id', $userId)->where('passed', true)->whereIn('quiz_id', Quiz::missions()->pluck('id'))->distinct('quiz_id')->count('quiz_id')
            : 0;
        $totalPointsAvailable = (int) Quiz::missions()->sum('points_reward');
        $earnedPoints = $userId
            ? (int) QuizAttempt::where('user_id', $userId)->where('passed', true)->sum('points_earned')
            : 0;

        return view('livewire.mission.index', [
            'missions' => $missions,
            'totalMissions' => $totalMissions,
            'completedMissionsCount' => $completedMissionsCount,
            'totalPointsAvailable' => $totalPointsAvailable,
            'earnedPoints' => $earnedPoints,
        ]);
    }
}
