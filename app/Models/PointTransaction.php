<?php

namespace App\Models;

use App\Observers\PointTransactionObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'ledger_type',
    'points',
    'source_type',
    'source_id',
    'description',
    'created_at',
])]
#[ObservedBy([PointTransactionObserver::class])]
class PointTransaction extends Model
{
    use HasFactory, HasUuids;

    public const LEDGER_XP = 'xp';

    public const LEDGER_POIN_CPS_ERA = 'poin_cps_era';

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the user who owns this point transaction.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope query untuk transaksi ledger XP (gamifikasi/level/leaderboard).
     */
    public function scopeXp($query)
    {
        return $query->where('ledger_type', self::LEDGER_XP);
    }

    /**
     * Scope query untuk transaksi ledger Poin CPS ERA (metrik formal HRD).
     */
    public function scopePoinCpsEra($query)
    {
        return $query->where('ledger_type', self::LEDGER_POIN_CPS_ERA);
    }
}
