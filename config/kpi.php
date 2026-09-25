<?php

return [
    /*
    |--------------------------------------------------------------------------
    | KPI Contribution Configuration
    |--------------------------------------------------------------------------
    |
    | KPI Contribution (keputusan owner): progres = jumlah materi Learning berbeda yang
    | post-test-nya lulus dengan skor tepat 100% di dalam periode KPI Settings. Dipakai oleh
    | KpiContributionCalculator, KpiContributionService, dan KpiSetting.
    | Blok 'points' milik VideoApprovalService (kontribusi video, tidak dipakai KPI).
    |
    */

    /**
     * Zona waktu batas hari periode KPI. app.timezone tetap UTC: awal periode = period_start
     * 00:00:00 di zona ini dan akhir = period_end 23:59:59 di zona ini, dikonversi ke UTC saat query.
     */
    'timezone' => 'Asia/Jakarta',

    /**
     * Target default jumlah materi berbeda per periode.
     */
    'default_target_materials' => 5,

    /**
     * Capping persentase KPI Contribution. DIPUTUSKAN owner: persentase selalu dibatasi
     * maksimal 100% (progres dibatasi pada target). Sebelumnya: menunggu keputusan PRD §5.3.
     */
    'cap_at_100_percent' => true,

    /**
     * Poin yang diberikan kepada pembuat video saat video dinyatakan PUBLISHED
     * melalui 2 tahap verifikasi (Atasan/Supervisor lalu HR/Quality).
     */
    'points' => [
        // Poin untuk video jalur A (Wajib akibat insiden/BA)
        'mandatory_video_published' => 100,

        // Poin untuk video jalur B (Sukarela / improvement)
        'voluntary_video_published' => 100,

        // Poin reward default untuk kuis post-test video (diperoleh penonton saat lulus)
        'video_post_test_passed' => 25,
    ],
];
