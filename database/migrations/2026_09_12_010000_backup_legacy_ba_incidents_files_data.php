<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Backup data legacy berkas file_ba_url & file_ftk_url sebelum kolom dihapus di migrasi berikutnya.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ba_incident_file_backups')) {
            Schema::create('ba_incident_file_backups', function (Blueprint $table) {
                $table->id();
                $table->uuid('ba_incident_id');
                $table->string('nomor_ba', 20)->nullable();
                $table->string('file_ba_url')->nullable();
                $table->string('file_ftk_url')->nullable();
                $table->timestamps();
            });
        }

        // Salin data lama jika kolom masih ada di ba_incidents
        if (Schema::hasTable('ba_incidents') && Schema::hasColumn('ba_incidents', 'file_ba_url') && Schema::hasColumn('ba_incidents', 'file_ftk_url')) {
            $legacyRecords = DB::table('ba_incidents')
                ->whereNotNull('file_ba_url')
                ->orWhereNotNull('file_ftk_url')
                ->get(['id', 'nomor_ba', 'file_ba_url', 'file_ftk_url']);

            foreach ($legacyRecords as $record) {
                DB::table('ba_incident_file_backups')->insert([
                    'ba_incident_id' => $record->id,
                    'nomor_ba' => $record->nomor_ba,
                    'file_ba_url' => $record->file_ba_url,
                    'file_ftk_url' => $record->file_ftk_url,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ba_incident_file_backups');
    }
};
