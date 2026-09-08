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
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('learning_category_id')->constrained('learning_categories')->cascadeOnDelete();
            $table->string('title');
            $table->string('type');
            $table->string('content_url')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('published');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_materials');
    }
};
