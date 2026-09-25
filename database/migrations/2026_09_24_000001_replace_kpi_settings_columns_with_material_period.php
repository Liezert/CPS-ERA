<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * KPI Contribution berbasis materi Learning (keputusan owner): periode ditentukan admin lewat
 * tanggal mulai & akhir, dengan target jumlah materi. Kolom KPI video lama (target_video_count,
 * period_type, points_reward) di-drop atas keputusan owner karena datanya masih dummy/development.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_settings', function (Blueprint $table) {
            $table->date('period_start')->nullable()->after('id');
            $table->date('period_end')->nullable()->after('period_start');
            $table->unsignedInteger('target_materials')->default(5)->after('period_end');
        });

        // Baris lama mendapat periode aman: tahun berjalan (zona KPI).
        $now = now(config('kpi.timezone', 'Asia/Jakarta'));
        DB::table('kpi_settings')->whereNull('period_start')->update([
            'period_start' => $now->copy()->startOfYear()->toDateString(),
            'period_end' => $now->copy()->endOfYear()->toDateString(),
        ]);

        Schema::table('kpi_settings', function (Blueprint $table) {
            $table->dropColumn(['target_video_count', 'period_type', 'points_reward']);
        });
    }

    public function down(): void
    {
        Schema::table('kpi_settings', function (Blueprint $table) {
            $table->integer('target_video_count')->default(10);
            $table->string('period_type')->default('monthly');
            $table->integer('points_reward')->default(50);
        });

        Schema::table('kpi_settings', function (Blueprint $table) {
            $table->dropColumn(['period_start', 'period_end', 'target_materials']);
        });
    }
};
