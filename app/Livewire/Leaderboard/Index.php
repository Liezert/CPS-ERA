<?php

namespace App\Livewire\Leaderboard;

use App\Models\Division;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Leaderboard - CPS ERA')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'division')]
    public string $selectedDivision = 'all';

    #[Url(as: 'period')]
    public string $period = 'all_time';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedDivision(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedDivision']);
        $this->resetPage();
    }

    public function render(): View
    {
        /** @var User|null $currentUser */
        $currentUser = Auth::user();

        // 1. Query Pengguna Terurut Berdasarkan XP (DoD #1: Sorting berdasarkan XP benar)
        $query = User::with('division')
            ->orderBy('xp', 'desc')
            ->orderBy('name', 'asc')
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->selectedDivision !== 'all', function ($q) {
                $q->where('division_id', $this->selectedDivision);
            });

        $users = $query->paginate(15);

        // 2. Hitung Posisi Peringkat Individual Pengguna Saat Ini
        $myRank = $currentUser
            ? User::where('xp', '>', (int) ($currentUser->xp ?? 0))->count() + 1
            : null;

        // 3. Daftar Divisi untuk Opsi Filter
        $divisions = Division::orderBy('name')->get();

        return view('livewire.leaderboard.index', [
            'users' => $users,
            'currentUser' => $currentUser,
            'myRank' => $myRank,
            'divisions' => $divisions,
        ]);
    }
}
