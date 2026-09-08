<?php

namespace App\Services;

class LevelCalculator
{
    /**
     * Hitung level user berdasarkan total poin yang dikumpulkan.
     * Sesuai catatan PRD 5.3 poin 1, rumus resmi skala level belum difinalkan.
     * Implementasi placeholder:
     * - Level awal = 1 (untuk totalPoints < 2000)
     * - Setiap 2000 poin = naik 1 level
     * Formula: max(1, (int) floor($totalPoints / 2000) + 1)
     * Contoh:
     *  0 - 1999 poin  => Level 1
     *  2000 - 3999    => Level 2
     *  4000 - 5999    => Level 3
     *  dst.
     */
    public function calculate(int $totalPoints): int
    {
        if ($totalPoints <= 0) {
            return 1;
        }

        return max(1, (int) floor($totalPoints / 2000) + 1);
    }

    /**
     * Hitung sisa poin yang dibutuhkan untuk mencapai level berikutnya.
     */
    public function pointsToNextLevel(int $totalPoints): int
    {
        $currentLevel = $this->calculate($totalPoints);
        $nextThreshold = $currentLevel * 2000;

        return max(0, $nextThreshold - max(0, $totalPoints));
    }

    /**
     * Hitung batas poin untuk level berikutnya.
     */
    public function nextLevelThreshold(int $totalPoints): int
    {
        return $this->calculate($totalPoints) * 2000;
    }

    /**
     * Hitung persentase progres poin pada level saat ini (0 - 100%).
     */
    public function levelProgressPercent(int $totalPoints): int
    {
        if ($totalPoints <= 0) {
            return 0;
        }

        $currentLevel = $this->calculate($totalPoints);
        $levelBase = ($currentLevel - 1) * 2000;
        $pointsInCurrentLevel = $totalPoints - $levelBase;

        return min(100, max(0, (int) round(($pointsInCurrentLevel / 2000) * 100)));
    }
}
