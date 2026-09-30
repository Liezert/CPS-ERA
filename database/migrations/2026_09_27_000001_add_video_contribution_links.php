<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Video kontribusi terpisah dari laporan CAPA (keputusan owner 2026-09-27). Additive saja:
 * - videos.learning_category_id: kategori Learning yang dipilih pengunggah.
 * - learning_materials.source_video_id: materi Learning yang terbit dari video yang disetujui HR.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('learning_category_id')->nullable()->after('division_id')
                ->constrained('learning_categories')->nullOnDelete();
        });

        Schema::table('learning_materials', function (Blueprint $table) {
            $table->foreignUuid('source_video_id')->nullable()->after('source_ba_id')
                ->constrained('videos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('learning_materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_video_id');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('learning_category_id');
        });
    }
};
