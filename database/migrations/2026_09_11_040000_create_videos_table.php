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
        Schema::create('videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url');
            $table->foreignId('division_id')->constrained('divisions');
            $table->foreignId('created_by')->constrained('users');

            // creation_reason: mandatory_incident (Jalur A) vs voluntary_improvement (Jalur B)
            $table->string('creation_reason'); // 'mandatory_incident', 'voluntary_improvement'
            $table->foreignUuid('ba_incident_id')->nullable()->constrained('ba_incidents')->nullOnDelete();

            // Status 2-tahap approval: pending_supervisor -> pending_hr -> published (atau rejected)
            $table->string('status')->default('pending_supervisor'); // 'pending_supervisor', 'pending_hr', 'published', 'rejected'

            // Tahap 1: Review Atasan / Supervisor Divisi
            $table->foreignId('supervisor_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_reviewed_at')->nullable();
            $table->text('supervisor_notes')->nullable();

            // Tahap 2: Review HR / Quality
            $table->foreignId('hr_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_reviewed_at')->nullable();
            $table->text('hr_notes')->nullable();

            // Catatan jika ditolak
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
