<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_kpi_yearly', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('period_year');
            $table->smallInteger('materials_completed_count')->default(0);
            $table->smallInteger('poin_cps_era_earned')->default(0);
            $table->smallInteger('poin_from_ba')->default(0);
            $table->smallInteger('poin_from_materi')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'period_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_kpi_yearly');
    }
};
