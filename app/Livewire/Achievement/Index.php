<?php

namespace App\Livewire\Achievement;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    /**
     * Evaluasi kriteria unlock achievement untuk pengguna.
     *
     * TODO: Menunggu keputusan PRD §5.3 (Poin 5: Kriteria unlock achievement).
     * Logika kriteria otomatisasi unlock (misal: N laporan BA selesai, N kuis lulus, total XP tertentu)
     * belum diputuskan oleh manajemen/stakeholder.
     * Sesuai instruksi: DILARANG mengarang formula atau kriteria otomatisasi.
     * Default interim: Murni render berbasis data pivot `user_achievements` (unlocked_at).
     */
    public function evaluateUnlockCriteria(): void
    {
        // TODO: Menunggu keputusan PRD §5.3 (Poin 5: Kriteria unlock achievement).
    }

    /**
     * Render grid achievement katalog.
     */
    public function render(): View
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Ambil data user_achievements milik user saat ini
        $userAchievements = $user
            ? $user->userAchievements()->get()->keyBy('achievement_id')
            : collect();

        // Ambil semua katalog achievement dan petakan status Unlocked / Locked
        // TODO: Menunggu keputusan PRD §5.3 (Poin 5: Kriteria unlock achievement).
        $achievements = Achievement::orderBy('id', 'asc')->get()->map(function (Achievement $achievement) use ($userAchievements) {
            $userAchievement = $userAchievements->get($achievement->id);
            $achievement->is_unlocked = ! is_null($userAchievement);
            $achievement->unlocked_at = $userAchievement?->unlocked_at;

            return $achievement;
        });

        $totalCount = $achievements->count();
        $unlockedCount = $achievements->where('is_unlocked', true)->count();
        $lockedCount = $totalCount - $unlockedCount;

        return view('livewire.achievement.index', [
            'achievements' => $achievements,
            'totalCount' => $totalCount,
            'unlockedCount' => $unlockedCount,
            'lockedCount' => $lockedCount,
        ])->layout('layouts.app', [
            'title' => 'Pencapaian & Lencana — CPS-ERA',
        ]);
    }
}
