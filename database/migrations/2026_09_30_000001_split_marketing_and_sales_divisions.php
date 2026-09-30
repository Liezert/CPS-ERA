<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keputusan owner (2026-09-30, permintaan klien): Marketing dan Sales kembali menjadi dua divisi
 * terpisah, sehingga daftar divisi menjadi 13 (12 divisi pelapor + HRGA).
 *
 * "Marketing & Sales" di-rename menjadi "Marketing" (ID tetap) dan "Sales" dibuat baru. Record yang dulu
 * dipindah dari Sales saat penggabungan (tercatat di `division_merge_backup`) dikembalikan ke Sales;
 * record lain tetap di Marketing dan bisa dipindah manual oleh admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $merged = DB::table('divisions')->where('name', 'Marketing & Sales')->first();

            if (! $merged) {
                return;
            }

            DB::table('divisions')->where('id', $merged->id)->update(['name' => 'Marketing', 'updated_at' => now()]);
            $salesId = DB::table('divisions')->where('name', 'Sales')->value('id')
                ?? DB::table('divisions')->insertGetId(['name' => 'Sales', 'created_at' => now(), 'updated_at' => now()]);

            if (Schema::hasTable('division_merge_backup')) {
                foreach (DB::table('division_merge_backup')->where('action', 'reassigned_record')->get() as $entry) {
                    DB::table($entry->table_name)->where('id', $entry->record_id)->where('division_id', $merged->id)->update(['division_id' => $salesId]);
                }
            }
        });

        Schema::dropIfExists('division_merge_backup');
    }

    public function down(): void
    {
        // Tidak dibalik otomatis: penggabungan ulang adalah keputusan bisnis, bukan rollback teknis.
    }
};
