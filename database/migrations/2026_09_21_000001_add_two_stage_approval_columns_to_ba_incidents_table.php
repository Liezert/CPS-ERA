<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom alur approval CAPA dua tahap: Supervisor (divisi pelapor) → HR (final).
 *
 * Penamaan mengikuti pola tabel `videos` yang sudah lebih dulu memakai alur dua
 * tahap yang sama (supervisor_reviewed_by / supervisor_reviewed_at).
 *
 * Yang sengaja TIDAK ditambahkan karena padanannya sudah ada:
 * - Divisi pelapor: `division_id` sudah menjadi snapshot saat pengisian form.
 * - Tahap HR: memakai ulang `reviewed_by` / `reviewed_at` (reviewer final yang
 *   memicu poin & publikasi). Laporan yang disetujui sebelum alur dua tahap ada
 *   akan terbaca "disetujui HR oleh Admin" — itu reinterpretasi label untuk data
 *   lama, bukan data yang salah.
 * - Catatan per tahap: `catatan_penolakan` tetap satu kolom yang dibaca pelapor;
 *   jejak siapa menolak di tahap mana direkam activity log (Stage 2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ba_incidents', function (Blueprint $table) {
            $table->foreignId('supervisor_reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('supervisor_reviewed_at')->nullable();

            // Penanda idempotensi: poin hanya diberikan sekali per laporan.
            $table->timestamp('points_awarded_at')->nullable();
            $table->timestamp('published_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ba_incidents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_reviewed_by');
            $table->dropColumn(['supervisor_reviewed_at', 'points_awarded_at', 'published_at']);
        });
    }
};
