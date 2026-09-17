<?php

namespace App\Services;

use App\Models\BaIncident;
use App\Models\LearningMaterial;
use App\Models\PointTransaction;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserKpiYearly;
use Illuminate\Support\Facades\DB;

class KpiContributionService
{
    /**
     * Dapatkan atau inisialisasi record tahunan KPI untuk seorang user (lazy initialization).
     */
    public function getOrCreateYearlyRecord(User $user, ?int $year = null): UserKpiYearly
    {
        return $user->getKpiYearly($year);
    }

    /**
     * Catat penyelesaian materi pembelajaran (Jalur B KPI Contribution).
     * Syarat lulus & kredit: score persis 100%.
     * Setiap 5 materi unik di tahun berjalan memberikan +1 Poin CPS ERA (maks 3/tahun gabungan).
     *
     * @param  User  $user  User yang menyelesaikan
     * @param  LearningMaterial  $material  Materi yang diselesaikan
     * @param  int  $score  Skor post-test (0-100)
     * @param  QuizAttempt|null  $currentAttempt  Attempt saat ini untuk pengecekan idempotensi
     */
    public function recordMaterialCompletion(
        User $user,
        LearningMaterial $material,
        int $score,
        ?QuizAttempt $currentAttempt = null
    ): ?UserKpiYearly {
        $year = (int) now()->year;

        return DB::transaction(function () use ($user, $material, $score, $currentAttempt, $year): ?UserKpiYearly {
            // 1. Berikan XP opsional materi jika ada dan belum pernah diberikan
            if ($material->xp_reward && $material->xp_reward > 0) {
                $hasReceivedXp = PointTransaction::where('user_id', $user->id)
                    ->where('ledger_type', PointTransaction::LEDGER_XP)
                    ->where('source_type', 'learning_material_xp')
                    ->where('source_id', $material->id)
                    ->exists();

                if (! $hasReceivedXp) {
                    PointTransaction::create([
                        'user_id' => $user->id,
                        'ledger_type' => PointTransaction::LEDGER_XP,
                        'points' => (int) $material->xp_reward,
                        'source_type' => 'learning_material_xp',
                        'source_id' => $material->id,
                        'description' => "XP Materi: {$material->title}",
                        'created_at' => now(),
                    ]);
                }
            }

            // 2. Kredit KPI HANYA diberikan jika score = 100 persis
            if ($score !== 100) {
                return null;
            }

            // 3. Cek idempotensi: materi yang sama tidak dihitung dobel dalam tahun yang sama
            $postTest = $material->postTest;
            if ($postTest) {
                $alreadyCreditedThisYear = QuizAttempt::where('user_id', $user->id)
                    ->where('quiz_id', $postTest->id)
                    ->where('score', 100)
                    ->where('passed', true)
                    ->whereYear('attempted_at', $year)
                    ->when($currentAttempt, fn ($q) => $q->where('id', '!=', $currentAttempt->id))
                    ->exists();

                if ($alreadyCreditedThisYear) {
                    return $this->getOrCreateYearlyRecord($user, $year);
                }
            }

            $kpiYearly = $this->getOrCreateYearlyRecord($user, $year);

            // 4. Increment materials_completed_count
            $newCount = $kpiYearly->materials_completed_count + 1;

            if ($newCount >= 5) {
                // Reset counter kembali ke 0 setiap kelipatan 5
                $kpiYearly->materials_completed_count = 0;

                // Cek cap tahunan (maksimal 3 Poin CPS ERA per tahun gabungan)
                if ($kpiYearly->poin_cps_era_earned < 3) {
                    $kpiYearly->poin_cps_era_earned += 1;
                    $kpiYearly->poin_from_materi += 1;

                    // Catat ke buku besar point_transactions
                    PointTransaction::create([
                        'user_id' => $user->id,
                        'ledger_type' => PointTransaction::LEDGER_POIN_CPS_ERA,
                        'points' => 1,
                        'source_type' => 'kpi_materi_bundle_completed',
                        'source_id' => null,
                        'description' => "Poin CPS ERA: Menyelesaikan bundle 5 materi Learning (Tahun {$year})",
                        'created_at' => now(),
                    ]);
                }
            } else {
                $kpiYearly->materials_completed_count = $newCount;
            }

            $kpiYearly->save();

            return $kpiYearly->fresh();
        });
    }

    /**
     * Catat persetujuan BA & video penanganan (Jalur A KPI Contribution).
     * Memberikan +1 Poin CPS ERA ke pembuat BA selama cap tahunan (3 poin) belum tercapai.
     *
     * @param  User  $user  Pembuat BA (incident->creator)
     * @param  BaIncident  $incident  BA yang disetujui
     */
    public function recordBaVideoApproved(User $user, BaIncident $incident): UserKpiYearly
    {
        $year = (int) now()->year;

        return DB::transaction(function () use ($user, $incident, $year): UserKpiYearly {
            $kpiYearly = $this->getOrCreateYearlyRecord($user, $year);

            // Cek cap tahunan 3 Poin CPS ERA
            if ($kpiYearly->poin_cps_era_earned < 3) {
                $kpiYearly->poin_cps_era_earned += 1;
                $kpiYearly->poin_from_ba += 1;
                $kpiYearly->save();

                PointTransaction::create([
                    'user_id' => $user->id,
                    'ledger_type' => PointTransaction::LEDGER_POIN_CPS_ERA,
                    'points' => 1,
                    'source_type' => 'ba_video_approved',
                    'source_id' => $incident->id,
                    'description' => "Poin CPS ERA: BA & Video Penanganan Disetujui ({$incident->nomor_ba})",
                    'created_at' => now(),
                ]);
            }

            return $kpiYearly->fresh();
        });
    }
}
