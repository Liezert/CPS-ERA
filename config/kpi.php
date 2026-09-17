<?php

return [
    /*
    |--------------------------------------------------------------------------
    | KPI Contribution & Video Contribution Configuration
    |--------------------------------------------------------------------------
    |
    | Pengaturan default ini digunakan oleh KpiContributionCalculator dan
    | VideoApprovalService.
    |
    | CATATAN PRD §5.3 & Client Review:
    | Parameter di bawah adalah PLACEHOLDER dan wajib disesuaikan begitu
    | keputusan resmi client telah difinalkan.
    |
    */

    /**
     * Target minimal video yang kuis post-test-nya harus lulus per periode (placeholder).
     */
    'default_target_video_count' => 10,

    /**
     * Tipe periode default untuk penghitungan KPI ('monthly', 'quarterly', 'all_time').
     */
    'default_period_type' => 'monthly',

    /**
     * Capping maksimal persentase KPI Contribution (100%).
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
