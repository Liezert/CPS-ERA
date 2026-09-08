<?php

namespace App\Observers;

use App\Models\PointTransaction;
use App\Models\User;
use App\Services\LevelCalculator;

class PointTransactionObserver
{
    public function __construct(
        protected LevelCalculator $levelCalculator
    ) {}

    /**
     * Handle the PointTransaction "created" event.
     */
    public function created(PointTransaction $pointTransaction): void
    {
        $this->syncUserPointsAndLevel($pointTransaction->user_id);
    }

    /**
     * Handle the PointTransaction "updated" event.
     */
    public function updated(PointTransaction $pointTransaction): void
    {
        $this->syncUserPointsAndLevel($pointTransaction->user_id);

        if ($pointTransaction->wasChanged('user_id') && $pointTransaction->getOriginal('user_id')) {
            $this->syncUserPointsAndLevel((string) $pointTransaction->getOriginal('user_id'));
        }
    }

    /**
     * Handle the PointTransaction "deleted" event.
     */
    public function deleted(PointTransaction $pointTransaction): void
    {
        $this->syncUserPointsAndLevel($pointTransaction->user_id);
    }

    /**
     * Recompute user's total_points from SUM(point_transactions.points)
     * and update user's level via LevelCalculator.
     */
    protected function syncUserPointsAndLevel(string $userId): void
    {
        $totalPoints = (int) PointTransaction::where('user_id', $userId)->sum('points');
        $level = $this->levelCalculator->calculate($totalPoints);

        User::where('id', $userId)->update([
            'total_points' => $totalPoints,
            'level' => $level,
        ]);
    }
}
