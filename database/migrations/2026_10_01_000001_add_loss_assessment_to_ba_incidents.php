<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan potensi kerugian dari Supervisor saat review tahap 1: ada/tidak, rekomendasi nilai ganti rugi,
 * dan siapa saja yang menanggung beserta nominalnya (JSON: [{nama, nominal}]).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ba_incidents', function (Blueprint $table) {
            $table->boolean('potensi_kerugian')->nullable()->after('is_potensi_peluang');
            $table->unsignedBigInteger('nilai_kerugian')->nullable()->after('potensi_kerugian');
            $table->json('penanggung_kerugian')->nullable()->after('nilai_kerugian');
        });
    }

    public function down(): void
    {
        Schema::table('ba_incidents', function (Blueprint $table) {
            $table->dropColumn(['potensi_kerugian', 'nilai_kerugian', 'penanggung_kerugian']);
        });
    }
};
