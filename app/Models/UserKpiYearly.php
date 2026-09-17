<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'period_year',
    'materials_completed_count',
    'poin_cps_era_earned',
    'poin_from_ba',
    'poin_from_materi',
])]
class UserKpiYearly extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_kpi_yearly';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'materials_completed_count' => 'integer',
            'poin_cps_era_earned' => 'integer',
            'poin_from_ba' => 'integer',
            'poin_from_materi' => 'integer',
        ];
    }

    /**
     * Get the user that owns this yearly KPI record.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
