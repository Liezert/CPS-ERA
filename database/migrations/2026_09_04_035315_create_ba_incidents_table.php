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
        Schema::create('ba_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nomor_ba', 20)->unique();
            $table->foreignId('division_id')->constrained('divisions');
            $table->string('file_ba_url');
            $table->string('file_ftk_url');
            $table->enum('status', ['created', 'reviewed', 'closed'])->default('created');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ba_incidents');
    }
};
