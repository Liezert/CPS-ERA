<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rekonsiliasi 13 divisi lama ke 12 divisi final dari client (+ HRGA).
 *
 * - `Gudang RM` di-rename menjadi `RM Warehouse` (ID tetap, user tetap menunjuk ke sana).
 * - Divisi baru ditambahkan: Jahit, Plant Balben & Krian, Marketing, Sales.
 * - Divisi tanpa padanan (IT, Keamanan, Repair, Sales & Marketing) dihapus setelah semua
 *   record yang menunjuk ke sana dipindah. Data (bukan user) di HRGA juga dipindah karena
 *   HRGA bukan divisi pelapor; baris HRGA sendiri tetap ada untuk profil user HRGA.
 * - Target pemindahan ditentukan crc32(id record) modulo 12 atas divisi final yang diurutkan
 *   per nama, sehingga hasilnya sama di setiap lingkungan dan setiap kali dijalankan ulang.
 *
 * Setiap langkah dicatat di `division_reconciliation_backup` supaya `down()` bisa mengembalikan
 * data persis ke keadaan semula. Pada instalasi baru (tabel divisi kosong, DivisionSeeder sudah
 * memakai daftar final) migration ini tidak mengubah apa pun.
 */
return new class extends Migration
{
    private const BACKUP_TABLE = 'division_reconciliation_backup';

    /** Tabel yang punya kolom `division_id` (FK ke divisions). */
    private const REFERENCING_TABLES = ['users', 'ba_incidents', 'knowledge_documents', 'videos'];

    private const RENAMES = ['Gudang RM' => 'RM Warehouse'];

    private const NEW_DIVISIONS = ['Jahit', 'Plant Balben & Krian', 'Marketing', 'Sales'];

    private const RETIRED_DIVISIONS = ['IT', 'Keamanan', 'Repair', 'Sales & Marketing'];

    /** Divisi yang tetap ada, tapi datanya (selain user) harus dipindah. */
    private const DATA_ONLY_DIVISIONS = ['HRGA'];

    private const FINAL_DIVISIONS = [
        'Produksi',
        'PPIC',
        'Warehouse & Delivery',
        'RM Warehouse',
        'Engineering',
        'Quality Control',
        'Jahit',
        'Finance Accounting Tax',
        'Purchasing',
        'Marketing',
        'Sales',
        'Plant Balben & Krian',
    ];

    public function up(): void
    {
        Schema::create(self::BACKUP_TABLE, function (Blueprint $table) {
            $table->id();
            // renamed_division | inserted_division | reassigned_record | deleted_division
            $table->string('action', 30);
            $table->string('table_name', 64)->nullable();
            $table->string('record_id', 36)->nullable();
            // Divisi asal record, atau ID divisi yang di-rename/ditambah/dihapus.
            $table->unsignedBigInteger('division_id');
            // Nama asli divisi yang di-rename/dihapus.
            $table->string('division_name', 100)->nullable();
        });

        if (! $this->hasLegacyLayout()) {
            return;
        }

        DB::transaction(function (): void {
            $this->renameDivisions();
            $this->insertNewDivisions();
            $this->reassignRecords();
            $this->deleteRetiredDivisions();
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
                if (! DB::table('divisions')->where('id', $entry->division_id)->exists()) {
                    DB::table('divisions')->insert([
                        'id' => $entry->division_id,
                        'name' => $entry->division_name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            foreach ($entries->get('reassigned_record', []) as $entry) {
                DB::table($entry->table_name)
                    ->where('id', $entry->record_id)
                    ->update(['division_id' => $entry->division_id]);
            }

            $insertedIds = collect($entries->get('inserted_division', []))->pluck('division_id')->all();
            $this->assertUnreferenced($insertedIds, 'dihapus saat rollback');
            DB::table('divisions')->whereIn('id', $insertedIds)->delete();

            foreach ($entries->get('renamed_division', []) as $entry) {
                DB::table('divisions')->where('id', $entry->division_id)->update(['name' => $entry->division_name]);
            }
        });

        Schema::dropIfExists(self::BACKUP_TABLE);
    }

    private function hasLegacyLayout(): bool
    {
        $legacyNames = array_merge(array_keys(self::RENAMES), self::RETIRED_DIVISIONS);

        return DB::table('divisions')->whereIn('name', $legacyNames)->exists();
    }

    private function renameDivisions(): void
    {
        foreach (self::RENAMES as $from => $to) {
            $division = DB::table('divisions')->where('name', $from)->first();

            if (! $division || DB::table('divisions')->where('name', $to)->exists()) {
                continue;
            }

            DB::table('divisions')->where('id', $division->id)->update(['name' => $to, 'updated_at' => now()]);
            $this->backup('renamed_division', divisionId: (int) $division->id, divisionName: $from);
        }
    }

    private function insertNewDivisions(): void
    {
        foreach (self::NEW_DIVISIONS as $name) {
            if (DB::table('divisions')->where('name', $name)->exists()) {
                continue;
            }

            $id = DB::table('divisions')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            $this->backup('inserted_division', divisionId: $id);
        }
    }

    private function reassignRecords(): void
    {
        $targets = $this->finalDivisionIdsSortedByName();

        $sources = DB::table('divisions')
            ->whereIn('name', array_merge(self::RETIRED_DIVISIONS, self::DATA_ONLY_DIVISIONS))
            ->get(['id', 'name']);

        foreach ($sources as $source) {
            $dataOnly = in_array($source->name, self::DATA_ONLY_DIVISIONS, true);

            foreach (self::REFERENCING_TABLES as $table) {
                if ($dataOnly && $table === 'users') {
                    continue;
                }

                foreach (DB::table($table)->where('division_id', $source->id)->pluck('id') as $recordId) {
                    $this->backup('reassigned_record', $table, (string) $recordId, (int) $source->id);

                    DB::table($table)->where('id', $recordId)->update([
                        'division_id' => $targets[crc32((string) $recordId) % count($targets)],
                    ]);
                }
            }
        }
    }

    private function deleteRetiredDivisions(): void
    {
        $retired = DB::table('divisions')->whereIn('name', self::RETIRED_DIVISIONS)->get(['id', 'name']);

        // users.division_id memakai nullOnDelete: tanpa assert ini, user yang terlewat
        // dipindah akan kehilangan divisinya diam-diam alih-alih menggagalkan migration.
        $this->assertUnreferenced($retired->pluck('id')->all(), 'dihapus');

        foreach ($retired as $division) {
            $this->backup('deleted_division', divisionId: (int) $division->id, divisionName: $division->name);
        }

        DB::table('divisions')->whereIn('id', $retired->pluck('id'))->delete();
    }

    /**
     * @return list<int>
     */
    private function finalDivisionIdsSortedByName(): array
    {
        $names = self::FINAL_DIVISIONS;
        sort($names, SORT_STRING);

        $idsByName = DB::table('divisions')->whereIn('name', $names)->pluck('id', 'name');

        $missing = array_diff($names, $idsByName->keys()->all());
        if ($missing !== []) {
            throw new RuntimeException('Divisi final belum ada: '.implode(', ', $missing));
        }

        return array_map(fn (string $name): int => (int) $idsByName[$name], $names);
    }

    /**
     * @param  list<int>  $divisionIds
     */
    private function assertUnreferenced(array $divisionIds, string $operation): void
    {
        if ($divisionIds === []) {
            return;
        }

        foreach (self::REFERENCING_TABLES as $table) {
            $count = DB::table($table)->whereIn('division_id', $divisionIds)->count();

            if ($count > 0) {
                throw new RuntimeException(
                    'Divisi ['.implode(', ', $divisionIds)."] tidak bisa {$operation}: masih dirujuk {$count} baris di tabel {$table}."
                );
            }
        }
    }

    private function backup(
        string $action,
        ?string $table = null,
        ?string $recordId = null,
        ?int $divisionId = null,
        ?string $divisionName = null,
    ): void {
        DB::table(self::BACKUP_TABLE)->insert([
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'division_id' => $divisionId,
            'division_name' => $divisionName,
        ]);
    }
};
