<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Alur approval dua tahap: laporan yang sudah diserahkan (`submitted`, alur lama) masuk ke
 * antrean Supervisor (`pending_supervisor`).
 *
 * Status lama lain (`created`, `reviewed`, `closed`, dan `rejected` yang dulu berarti "perlu
 * revisi") tidak punya pemetaan yang sudah diputuskan, jadi migration berhenti alih-alih menebak.
 */
return new class extends Migration
{
    private const KNOWN_STATUSES = ['draft', 'submitted', 'approved'];

    public function up(): void
    {
        $unknown = DB::table('ba_incidents')
            ->whereNotIn('status', self::KNOWN_STATUSES)
            ->distinct()
            ->pluck('status');

        if ($unknown->isNotEmpty()) {
            throw new RuntimeException(
                'Status laporan lama tanpa pemetaan: '.$unknown->join(', ').'. Putuskan pemetaannya dulu sebelum migration ini dijalankan.'
            );
        }

        DB::table('ba_incidents')->where('status', 'submitted')->update(['status' => 'pending_supervisor']);
    }

    public function down(): void
    {
        // Kode lama memperlakukan `submitted` sebagai "menunggu review", padanan pending_supervisor.
        DB::table('ba_incidents')->where('status', 'pending_supervisor')->update(['status' => 'submitted']);
    }
};
