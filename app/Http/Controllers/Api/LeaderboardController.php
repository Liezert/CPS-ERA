<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    /**
     * Tampilkan leaderboard pengguna diurutkan berdasarkan total_points DESC.
     * Mendukung filter:
     * - period: 'all-time' (default), 'mingguan'|'weekly', 'bulanan'|'monthly'
     * - division / division_id: filter berdasarkan divisi tertentu
     *
     * GET /api/leaderboard?period=&division=
     */
    public function index(Request $request): JsonResponse
    {
        $period = (string) $request->query('period', 'all-time');
        $normalizedPeriod = strtolower($period);

        $startDate = match ($normalizedPeriod) {
            'mingguan', 'weekly' => now()->startOfWeek(),
            'bulanan', 'monthly' => now()->startOfMonth(),
            default => null,
        };

        $query = User::with('division');

        // Filter divisi (berdasarkan id angka atau nama divisi)
        if ($request->filled('division')) {
            $division = $request->query('division');
            if (is_numeric($division)) {
                $query->where('division_id', (int) $division);
            } else {
                $query->whereHas('division', fn ($q) => $q->where('name', $division));
            }
        } elseif ($request->filled('division_id')) {
            $query->where('division_id', $request->query('division_id'));
        }

        // Jika periode mingguan / bulanan, hitung perolehan poin pada periode tersebut
        if ($startDate !== null) {
            $query->withSum([
                'pointTransactions as period_points' => fn ($q) => $q->where('created_at', '>=', $startDate),
            ], 'points');
        }

        // Urutkan by total_points DESC (dan nama ASC sebagai tie-breaker)
        $users = $query->orderByDesc('total_points')
            ->orderBy('name')
            ->get();

        $rank = 1;
        $leaderboard = $users->map(function (User $user) use (&$rank, $startDate) {
            return [
                'rank' => $rank++,
                'id' => $user->id,
                'name' => $user->name,
                'employee_id' => $user->employee_id,
                'jabatan' => $user->jabatan,
                'avatar_url' => $user->avatar_url,
                'division' => $user->division ? [
                    'id' => $user->division->id,
                    'name' => $user->division->name,
                ] : null,
                'total_points' => (int) $user->total_points,
                'points' => $startDate !== null ? (int) ($user->period_points ?? 0) : (int) $user->total_points,
                'level' => (int) $user->level,
            ];
        });

        return response()->json([
            'success' => true,
            'period' => $period,
            'data' => $leaderboard,
        ]);
    }
}
