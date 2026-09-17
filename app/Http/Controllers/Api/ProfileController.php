<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserLearningProgress;
use App\Services\LevelCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Tampilkan data profil user yang sedang login beserta grafik performa bulanan (6 bulan terakhir).
     * GET /api/me/profile
     */
    public function show(Request $request, LevelCalculator $levelCalculator): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load(['division', 'roles']);

        $totalPoints = (int) ($user->xp ?? 0);
        $level = (int) $user->level;
        $pointsToNextLevel = $levelCalculator->pointsToNextLevel($totalPoints);
        $nextLevelThreshold = $levelCalculator->nextLevelThreshold($totalPoints);
        $levelProgressPercent = $levelCalculator->levelProgressPercent($totalPoints);

        // Agregasi performa XP 6 bulan terakhir dari point_transactions
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
        $transactions = PointTransaction::where('user_id', $user->id)
            ->where('ledger_type', 'xp')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->get();

        $monthlyPerformance = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $monthKey = $monthDate->format('Y-m');
            $monthLabel = $monthDate->format('M Y');

            $monthPoints = (int) $transactions
                ->filter(fn ($tx) => $tx->created_at->format('Y-m') === $monthKey)
                ->sum('points');

            $monthlyPerformance[] = [
                'month' => $monthKey,
                'label' => $monthLabel,
                'points' => $monthPoints,
            ];
        }

        // Rata-rata progress belajar
        $averageLearningProgress = (float) round(
            (float) (UserLearningProgress::where('user_id', $user->id)->avg('progress_percent') ?? 0),
            1
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'jabatan' => $user->jabatan,
                'avatar_url' => $user->avatar_url,
                'division' => $user->division ? [
                    'id' => $user->division->id,
                    'name' => $user->division->name,
                ] : null,
                'roles' => $user->getRoleNames(),

                'level' => $level,
                'xp' => $totalPoints,
                'total_points' => $totalPoints,
                'points_to_next_level' => $pointsToNextLevel,
                'next_level_threshold' => $nextLevelThreshold,
                'level_progress_percent' => $levelProgressPercent,

                // Metrik performa
                'average_learning_progress' => $averageLearningProgress,
                'kpi_contribution_percent' => null,
                'kpi_contribution_status' => 'not_implemented',

                // Grafik performa 6 bulan terakhir
                'monthly_performance' => $monthlyPerformance,
            ],
        ]);
    }

    /**
     * Perbarui data profil user yang sedang login.
     * PATCH /api/me/profile
     */
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'avatar_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'jabatan' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'jabatan' => $user->jabatan,
                'avatar_url' => $user->avatar_url,
                'division' => $user->division ? [
                    'id' => $user->division->id,
                    'name' => $user->division->name,
                ] : null,
            ],
        ]);
    }
}
