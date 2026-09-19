<?php

namespace App\Observers;

use App\Models\PointTransaction;
use App\Models\User;
use App\Services\LevelCalculator;

class PointTransactionObserver
{
    /**
     * Sinkronkan users.xp/level setelah transaksi commit, supaya X-lock pada baris users
     * tidak dipegang di dalam transaksi pemanggil (sumber deadlock approve BA bersamaan).
     * Aman karena sinkronisasi dihitung ulang dari SUM ledger, bukan increment.
     */
    public bool $afterCommit = true;

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
     * Recompute user's XP from SUM(point_transactions.points WHERE ledger_type = 'xp')
     * and update user's level via LevelCalculator.
     * Transaksi bertipe 'poin_cps_era' TIDAK dicampur ke users.xp.
     */
    protected function syncUserPointsAndLevel(string $userId): void
    {
        $xp = (int) PointTransaction::where('user_id', $userId)
            ->where('ledger_type', PointTransaction::LEDGER_XP)
            ->sum('points');
        $level = $this->levelCalculator->calculate($xp);

        User::where('id', $userId)->update([
            'xp' => $xp,
            'level' => $level,
        ]);
    }
}
