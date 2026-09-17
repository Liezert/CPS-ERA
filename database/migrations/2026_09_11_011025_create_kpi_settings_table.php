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
        Schema::create('kpi_settings', function (Blueprint $table) {
            $table->id();
            // PLACEHOLDER: Nilai target_video_count default 10, wajib disesuaikan dengan keputusan resmi client (PRD §5.3)
            $table->integer('target_video_count')->default(10);
            // PLACEHOLDER: Nilai period_type default 'monthly' ('monthly', 'quarterly', 'all_time'), wajib disesuaikan (PRD §5.3)
            $table->string('period_type')->default('monthly');
            // Poin bonus / reward opsional saat mencapai KPI 100%
            $table->integer('points_reward')->default(50);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_settings');
    }
};
