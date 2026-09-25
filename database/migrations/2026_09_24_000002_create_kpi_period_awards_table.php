<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jaminan idempotensi Poin CPS ERA dari materi: tepat satu poin per (user, periode KPI).
 * Unique constraint menahan pemberian ganda walau dua request berjalan bersamaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_period_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kpi_setting_id')->constrained('kpi_settings')->cascadeOnDelete();
            // Tahun kalender saat poin diberikan (baris user_kpi_yearlies yang menerima poin).
            $table->integer('award_year');
            $table->timestamps();

            $table->unique(['user_id', 'kpi_setting_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_period_awards');
    }
};
