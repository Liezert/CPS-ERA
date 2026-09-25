<?php

namespace App\Services;

use App\Models\KpiSetting;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * KPI Contribution (keputusan owner): progres = jumlah materi Learning BERBEDA yang punya minimal satu
 * attempt post-test dengan skor tepat 100 di dalam periode KPI Settings. Sumber kebenaran tunggal =
 * data quiz_attempts; tidak ada counter tersimpan, sehingga "reset" periode tidak menghapus data.
 *
 * Tidak dihitung: skor < 100, attempt di luar periode, attempt user lain, materi yang sama dua kali,
 * post-test video, dan attempt yatim milik materi yang sudah dihapus permanen.
 * Persentase selalu dibatasi maksimal 100% (DIPUTUSKAN owner, lihat config kpi.cap_at_100_percent).
 */
class KpiContributionCalculator
{
    /**
     * @return array{
     *     completed: int,
     *     target: int,
     *     percentage: int,
     *     is_complete: bool,
     *     summary: string,
     *     period_start: CarbonInterface,
     *     period_end: CarbonInterface,
     *     period_label: string
     * }
     */
    public function calculate(User $user, ?KpiSetting $setting = null): array
    {
        $setting ??= KpiSetting::current();
        $target = max(1, (int) $setting->target_materials);
        $completed = min($this->countCompletedMaterials($user, $setting), $target);

        return [
            'completed' => $completed,
            'target' => $target,
            'percentage' => intdiv($completed * 100, $target),
            'is_complete' => $completed >= $target,
            'summary' => "{$completed} dari {$target} materi",
            'period_start' => $setting->period_start,
            'period_end' => $setting->period_end,
            'period_label' => $setting->period_start->translatedFormat('d M Y').' – '.$setting->period_end->translatedFormat('d M Y'),
        ];
    }

    /**
     * Jumlah materi berbeda (tanpa batas target) yang lulus 100% di dalam periode.
     *
     * @param  string|null  $exceptAttemptId  Abaikan attempt ini (untuk mengetahui progres sebelum attempt tersebut).
     */
    public function countCompletedMaterials(User $user, KpiSetting $setting, ?string $exceptAttemptId = null): int
    {
        [$start, $end] = $setting->periodBoundsUtc();

        return QuizAttempt::query()
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            // Join ke materi: attempt milik materi yang sudah dihapus permanen tidak ikut dihitung.
            ->join('learning_materials', 'learning_materials.id', '=', 'quizzes.related_id')
            ->where('quiz_attempts.user_id', $user->id)
            ->where('quizzes.type', 'post_test')
            ->where('quizzes.related_type', 'learning_material')
            ->where('quiz_attempts.score', 100)
            ->where('quiz_attempts.passed', true)
            ->whereBetween('quiz_attempts.attempted_at', [$start, $end])
            ->when($exceptAttemptId, fn ($query) => $query->where('quiz_attempts.id', '!=', $exceptAttemptId))
            ->distinct()
            ->count('learning_materials.id');
    }
}
