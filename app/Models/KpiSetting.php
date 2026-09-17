<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'target_video_count',
    'period_type',
    'points_reward',
    'created_by',
])]
class KpiSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_video_count' => 'integer',
            'points_reward' => 'integer',
        ];
    }

    /**
     * User yang membuat konfigurasi ini.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Ambil pengaturan KPI aktif saat ini (terbaru).
     */
    public static function current(): self
    {
        return static::latest()->first() ?? new static([
            'target_video_count' => config('kpi.default_target_video_count', 10),
            'period_type' => config('kpi.default_period_type', 'monthly'),
            'points_reward' => 50,
        ]);
    }
}
