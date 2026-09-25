<?php

namespace App\Livewire;

use App\Models\BaIncident;
use App\Models\KnowledgeDocument;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserLearningProgress;
use App\Services\KpiContributionCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard - CPS ERA')]
class Dashboard extends Component
{
    public function render()
    {
        /** @var User $user */
        $user = Auth::user()->load('division');

        // 1. XP dari users.xp (Dual-ledger gamification)
        $xp = (int) ($user->xp ?? 0);

        // 2. User KPI Yearly (Tabel cache/agregat tahun berjalan)
        $kpiYearly = $user->getKpiYearly();

        // 3. Kalkulasi Progress Belajar (% materi yang ditandai selesai)
        $learningProgress = (int) round(
            UserLearningProgress::where('user_id', $user->id)
                ->avg('progress_percent') ?? 0
        );

        // 4. Peringkat Leaderboard (urutan XP yang sama dengan halaman Leaderboard)
        $leaderboardRank = User::where('xp', '>', $xp)->count() + 1;

        // 5. Knowledge Repository Terbaru (3 item terpublikasi untuk layout grid kartu)
        $latestKnowledge = KnowledgeDocument::with(['division', 'creator', 'topic'])
            ->published()
            ->latest()
            ->take(3)
            ->get();

        // 6. BA & Lesson Learned Terbaru (5 item)
        $latestBa = BaIncident::with(['division', 'creator'])
            ->latest()
            ->take(5)
            ->get();

        // 7. Materi Belajar yang Sedang Dipelajari (In-Progress: > 0% dan < 100%)
        $continueLearning = UserLearningProgress::where('user_id', $user->id)
            ->where('progress_percent', '>', 0)
            ->where('progress_percent', '<', 100)
            ->with('material.category')
            ->latest('updated_at')
            ->first();

        // 8. Misi yang Perlu Diselesaikan (Belum Selesai/Lulus, Tertua Lebih Dulu)
        $pendingMission = Quiz::missions()
            ->whereDoesntHave('attempts', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('passed', true);
            })
            ->withCount('questions')
            ->oldest('created_at')
            ->first();

        // Inisial avatar pengguna
        $initials = 'CP';
        if ($user && $user->name) {
            $parts = explode(' ', trim($user->name));
            $initials = strtoupper(substr($parts[0], 0, 1).(isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
        }

        return view('livewire.dashboard', [
            'user' => $user,
            'initials' => $initials,
            'xp' => $xp,
            'kpiYearly' => $kpiYearly,
            'kpi' => app(KpiContributionCalculator::class)->calculate($user),
            'learningProgress' => $learningProgress,
            'leaderboardRank' => $leaderboardRank,
            'latestKnowledge' => $latestKnowledge,
            'latestBa' => $latestBa,
            'continueLearning' => $continueLearning,
            'pendingMission' => $pendingMission,
        ]);
    }
}
