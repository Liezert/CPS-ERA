<?php

namespace App\Observers;

use App\Models\PointTransaction;
use App\Models\User;

class PointTransactionObserver
{
    /**
     * Sinkronkan users.xp setelah transaksi commit, supaya X-lock pada baris users
     * tidak dipegang di dalam transaksi pemanggil (sumber deadlock approve BA bersamaan).
     * Aman karena sinkronisasi dihitung ulang dari SUM ledger, bukan increment.
     */
    public bool $afterCommit = true;

    /**
     * Handle the PointTransaction "created" event.
     */
    public function created(PointTransaction $pointTransaction): void
    {
        $this->syncUserXp($pointTransaction->user_id);
    }

    /**
     * Handle the PointTransaction "updated" event.
     */
    public function updated(PointTransaction $pointTransaction): void
    {
        $this->syncUserXp($pointTransaction->user_id);

        if ($pointTransaction->wasChanged('user_id') && $pointTransaction->getOriginal('user_id')) {
            $this->syncUserXp((string) $pointTransaction->getOriginal('user_id'));
        }
    }

    /**
     * Handle the PointTransaction "deleted" event.
     */
    public function deleted(PointTransaction $pointTransaction): void
    {
        $this->syncUserXp($pointTransaction->user_id);
    }

    /**
     * Recompute user's XP from SUM(point_transactions.points WHERE ledger_type = 'xp').
     * Transaksi bertipe 'poin_cps_era' TIDAK dicampur ke users.xp.
     */
    protected function syncUserXp(string $userId): void
    {
        $xp = (int) PointTransaction::where('user_id', $userId)
            ->where('ledger_type', PointTransaction::LEDGER_XP)
            ->sum('points');

        User::where('id', $userId)->update(['xp' => $xp]);
    }
}
