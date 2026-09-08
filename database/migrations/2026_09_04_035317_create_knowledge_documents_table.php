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
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->foreignId('division_id')->constrained('divisions');
            $table->enum('type', ['dokumen', 'video', 'presentasi', 'lesson_learned', 'sop', 'link'])->default('lesson_learned');
            $table->string('file_url')->nullable();
            $table->string('external_link')->nullable();
            $table->text('description')->nullable();
            $table->foreignUuid('source_ba_id')->nullable()->constrained('ba_incidents')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->enum('status', ['draft', 'published'])->default('published');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_documents');
    }
};
