<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Perluas tabel videos dengan video_file_url dan video_external_link.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            if (! Schema::hasColumn('videos', 'video_file_url')) {
                $table->string('video_file_url')->nullable()->after('description');
            }
            if (! Schema::hasColumn('videos', 'video_external_link')) {
                $table->string('video_external_link')->nullable()->after('video_file_url');
            }
            if (Schema::hasColumn('videos', 'video_url')) {
                $table->string('video_url')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['video_file_url', 'video_external_link']);
        });
    }
};
