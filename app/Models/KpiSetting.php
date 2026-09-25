<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'period_start',
    'period_end',
    'target_materials',
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
            'period_start' => 'date',
            'period_end' => 'date',
            'target_materials' => 'integer',
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
     * Periode KPI aktif = pengaturan terbaru. Bila belum ada sama sekali, dibuat periode default
     * tahun berjalan (zona KPI) supaya periode selalu punya ID untuk idempotensi Poin CPS ERA.
     */
    public static function current(): self
    {
        $latest = static::query()->orderByDesc('id')->first();

        if ($latest) {
            return $latest;
        }

        $now = now(config('kpi.timezone'));

        return static::create([
            'period_start' => $now->copy()->startOfYear()->toDateString(),
            'period_end' => $now->copy()->endOfYear()->toDateString(),
            'target_materials' => (int) config('kpi.default_target_materials', 5),
        ]);
    }

    /**
     * Batas periode dalam UTC untuk query: period_start 00:00:00 s/d period_end 23:59:59
     * di zona KPI (Asia/Jakarta), inklusif.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function periodBoundsUtc(): array
    {
        $timezone = config('kpi.timezone');

        return [
            CarbonImmutable::parse($this->period_start->toDateString(), $timezone)->startOfDay()->utc(),
            CarbonImmutable::parse($this->period_end->toDateString(), $timezone)->endOfDay()->utc(),
        ];
    }
}
