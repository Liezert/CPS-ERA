<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keputusan owner (2026-09-24): Marketing dan Sales digabung menjadi satu divisi "Marketing & Sales",
 * sehingga daftar divisi menjadi 12 (11 divisi pelapor + HRGA).
 *
 * Baris `Marketing` di-rename (ID tetap); semua record yang menunjuk ke `Sales` dipindah ke sana,
 * lalu baris `Sales` dihapus. Setiap record yang dipindah dicatat di `division_merge_backup` supaya
 * `down()` bisa mengembalikannya persis. Instalasi baru (tabel divisi kosong) tidak terpengaruh;
 * DivisionSeeder langsung membuat "Marketing & Sales".
 */
return new class extends Migration
{
    private const BACKUP_TABLE = 'division_merge_backup';

    private const MERGED = 'Marketing & Sales';

    /** Tabel yang punya kolom `division_id` (FK ke divisions). */
    private const REFERENCING_TABLES = ['users', 'ba_incidents', 'knowledge_documents', 'videos'];

    public function up(): void
    {
        Schema::create(self::BACKUP_TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 64)->nullable();
            $table->string('record_id', 36)->nullable();
            // ID asli divisi Sales (baris `deleted_division` menyimpan nama aslinya juga).
            $table->unsignedBigInteger('division_id');
            $table->string('action', 30);
        });

        DB::transaction(function (): void {
            $marketing = DB::table('divisions')->where('name', 'Marketing')->first();
            $sales = DB::table('divisions')->where('name', 'Sales')->first();

            if (! $marketing && ! $sales) {
                return;
            }

            // Hanya salah satu yang ada: cukup rename.
            $keep = $marketing ?? $sales;
            DB::table('divisions')->where('id', $keep->id)->update(['name' => self::MERGED, 'updated_at' => now()]);
            $this->backup('renamed_division', divisionId: (int) $keep->id, recordId: $keep->name);

            if (! $marketing || ! $sales) {
                return;
            }

            foreach (self::REFERENCING_TABLES as $table) {
                foreach (DB::table($table)->where('division_id', $sales->id)->pluck('id') as $recordId) {
                    $this->backup('reassigned_record', $table, (string) $recordId, (int) $sales->id);
                }

                DB::table($table)->where('division_id', $sales->id)->update(['division_id' => $marketing->id]);
            }

            $this->backup('deleted_division', divisionId: (int) $sales->id, recordId: $sales->name);
            DB::table('divisions')->where('id', $sales->id)->delete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            return;
        }

        DB::transaction(function (): void {
            $entries = DB::table(self::BACKUP_TABLE)->orderBy('id')->get()->groupBy('action');

            foreach ($entries->get('deleted_division', []) as $entry) {
                DB::table('divisions')->insert([
                    'id' => $entry->division_id,
                    'name' => $entry->record_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($entries->get('reassigned_record', []) as $entry) {
                DB::table($entry->table_name)->where('id', $entry->record_id)->update(['division_id' => $entry->division_id]);
            }

            foreach ($entries->get('renamed_division', []) as $entry) {
                DB::table('divisions')->where('id', $entry->division_id)->update(['name' => $entry->record_id]);
            }
        });

        Schema::dropIfExists(self::BACKUP_TABLE);
    }

    private function backup(string $action, ?string $table = null, ?string $recordId = null, int $divisionId = 0): void
    {
        DB::table(self::BACKUP_TABLE)->insert([
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'division_id' => $divisionId,
        ]);
    }
};
