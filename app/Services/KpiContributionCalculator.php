<?php

namespace App\Services;

use App\Models\KpiSetting;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;

class KpiContributionCalculator
{
    /**
     * Hitung KPI Contribution % untuk seorang user.
     *
     * Logika Client-Approved:
     * 1. Target minimal video diambil dari tabel kpi_settings (kolom target_video_count & period_type).
     * 2. Menghitung jumlah DISTINCT video yang quiz post-test-nya (related_type = 'video')
     *    telah LULUS (passed = true) oleh user ini dalam periode aktif.
     * 3. Lulus post-test materi Learning biasa (related_type = 'learning_material')
     *    TIDAK ikut masuk hitungan KPI Contribution ini.
     * 4. Persentase = (jumlah video lulus post-test / target minimal) * 100%.
     * 5. Dibatasi maksimal 100% (capping ini adalah placeholder per PRD §5.3).
     *
     * @return array{
     *     passed_video_count: int,
     *     target_video_count: int,
     *     period_type: string,
     *     period_label: string,
     *     percentage: int,
     *     raw_percentage: float,
     *     is_capped: bool
     * }
     */
    public function calculate(User $user, ?Carbon $now = null): array
    {
        $now = $now ?? now();
        $setting = KpiSetting::current();

        $periodType = $setting->period_type ?? config('kpi.default_period_type', 'monthly');
        $targetCount = max(1, (int) ($setting->target_video_count ?? config('kpi.default_target_video_count', 10)));

        [$startDate, $endDate, $periodLabel] = $this->resolveDateRange($periodType, $now);

        $passedCount = $this->countPassedVideos($user, $startDate, $endDate);

        $rawPercentage = ($passedCount / $targetCount) * 100;
        $cappedPercentage = (int) min(100, round($rawPercentage));

        return [
            'passed_video_count' => $passedCount,
            'target_video_count' => $targetCount,
            'period_type' => $periodType,
            'period_label' => $periodLabel,
            'percentage' => $cappedPercentage,
            'raw_percentage' => (float) round($rawPercentage, 1),
            'is_capped' => $rawPercentage > 100,
        ];
    }

    /**
     * Hitung jumlah DISTINCT video yang kuis post-test-nya lulus oleh user dalam periode tertentu.
     * Hanya menghitung kuis bertipe post_test yang berelasi dengan video (related_type = 'video').
     */
    public function countPassedVideos(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        return QuizAttempt::query()
            ->where('quiz_attempts.user_id', $user->id)
            ->where('quiz_attempts.passed', true)
            ->whereHas('quiz', function ($q) {
                $q->where('type', 'post_test')
                    ->where('related_type', 'video')
                    ->whereNotNull('related_id');
            })
            ->when($startDate, fn ($q) => $q->where('quiz_attempts.attempted_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('quiz_attempts.attempted_at', '<=', $endDate))
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->distinct()
            ->count('quizzes.related_id');
    }

    /**
     * Tentukan batas awal dan akhir tanggal sesuai period_type.
     *
     * @return array{0: ?Carbon, 1: ?Carbon, 2: string}
     */
    protected function resolveDateRange(string $periodType, Carbon $now): array
    {
        return match ($periodType) {
            'quarterly' => [
                $now->copy()->startOfQuarter(),
                $now->copy()->endOfQuarter(),
                'Q'.$now->quarter.' '.$now->year,
            ],
            'all_time' => [
                null,
                null,
                'Sepanjang Waktu',
            ],
            default => [ // 'monthly'
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->translatedFormat('F Y'),
            ],
        };
    }
}
