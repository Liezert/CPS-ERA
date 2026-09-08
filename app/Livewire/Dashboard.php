<?php

namespace App\Livewire;

use App\Models\BaIncident;
use App\Models\KnowledgeDocument;
use App\Models\User;
use App\Models\UserLearningProgress;
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

        // 1. Agregasi Ledger Poin (Design System §8: BUKAN kolom counter statis)
        $totalPoints = (int) $user->pointTransactions()->sum('points');

        // 2. Kalkulasi Progress Belajar
        $learningProgress = (int) round(
            UserLearningProgress::where('user_id', $user->id)
                ->avg('progress_percent') ?? 0
        );

        // 3. Level & Target Level (TODO PRD §5.3 Poin 1)
        $currentLevel = $user->level ?? 2;
        $nextLevel = $currentLevel + 1;
        // Formula XP progress: placeholder 65% menuju level berikutnya
        $levelProgressPercent = 65;

        // 4. KPI Contribution (TODO PRD §5.3 Poin 2: Formula persentase KPI Contribution)
        $kpiContribution = null; // Menunggu formula resmi PRD §5.3

        // 5. Knowledge Repository Terbaru (5 item terpublikasi)
        $latestKnowledge = KnowledgeDocument::with(['division', 'creator'])
            ->published()
            ->latest()
            ->take(5)
            ->get();

        // 6. BA & Lesson Learned Terbaru (5 item)
        $latestBa = BaIncident::with(['division', 'creator'])
            ->latest()
            ->take(5)
            ->get();

        // Inisial avatar pengguna
        $initials = 'CP';
        if ($user && $user->name) {
            $parts = explode(' ', trim($user->name));
            $initials = strtoupper(substr($parts[0], 0, 1).(isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
        }

        return view('livewire.dashboard', [
            'user' => $user,
            'initials' => $initials,
            'totalPoints' => $totalPoints,
            'learningProgress' => $learningProgress,
            'currentLevel' => $currentLevel,
            'nextLevel' => $nextLevel,
            'levelProgressPercent' => $levelProgressPercent,
            'kpiContribution' => $kpiContribution,
            'latestKnowledge' => $latestKnowledge,
            'latestBa' => $latestBa,
        ]);
    }
}
