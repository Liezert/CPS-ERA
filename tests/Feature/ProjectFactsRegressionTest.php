<?php

namespace Tests\Feature;

use App\Models\Division;
use Database\Seeders\DivisionSeeder;
use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectFactsRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PRD v2.0 §1.4: Database Stack Fact Lock.
     * config('database.default') fallback harus 'mysql' setelah migrasi dari PostgreSQL.
     * (.env tetap mendefinisikan DB_CONNECTION per environment)
     */
    public function test_config_database_default_resolves_to_mysql(): void
    {
        // 1. Check direct configuration file fallback in config/database.php
        $configContent = file_get_contents(config_path('database.php'));
        $this->assertStringContainsString("'default' => env('DB_CONNECTION', 'mysql')", $configContent);

        // 2. Check .env.example references mysql as the target connection
        $exampleEnv = Dotenv::createArrayBacked(base_path(), '.env.example')->load();
        $this->assertSame('mysql', $exampleEnv['DB_CONNECTION'] ?? null);
    }

    /**
     * PRD v2.0 §1.4 & §2.1: Exactly 12 Divisions Fact Lock (Marketing & Sales digabung, keputusan owner 2026-09-24).
     * Division::count() must be exactly 12, and Division names must match the official list.
     */
    public function test_divisions_count_is_exactly_twelve_and_names_match_official_prd_v2_list(): void
    {
        $this->seed(DivisionSeeder::class);

        // 1. Exactly 12 divisions
        $this->assertSame(12, Division::count());

        // 2. 11 divisi pelapor final + HRGA
        $expectedDivisions = collect([
            'Engineering',
            'Finance Accounting Tax',
            'HRGA',
            'Jahit',
            'Marketing & Sales',
            'PPIC',
            'Plant Balben & Krian',
            'Produksi',
            'Purchasing',
            'Quality Control',
            'RM Warehouse',
            'Warehouse & Delivery',
        ])->sort()->values()->all();

        $actualDivisions = Division::pluck('name')->sort()->values()->all();

        $this->assertSame($expectedDivisions, $actualDivisions);
    }

    /**
     * PRD v2.0 §1.4 & §3.1: Non-ENUM Status Column Fact Lock.
     * ba_incidents.status must be stored as standard VARCHAR/TEXT, NOT a native database ENUM.
     * Status transition and validation are strictly enforced at the application/service level.
     */
    public function test_ba_incidents_status_column_is_not_native_enum(): void
    {
        /**
         * Penjelasan Desain Arsitektur (PRD v2.0 §1.4 & §3.1):
         * Kolom ba_incidents.status TIDAK dibuat sebagai ENUM native database.
         * Alasan:
         * 1. Menghindari DDL locking dan kompleksitas migrasi ALTER TABLE ... MODIFY pada MySQL
         *    production apabila di kemudian hari terdapat perubahan alur status.
         * 2. Validasi transisi status dijaga secara ketat di layer service (BaIncidentService)
         *    mengikuti aturan 4 tahap: draft -> submitted -> approved / rejected.
         * 3. Mempertahankan kompatibilitas test runner dan portabilitas SQLite in-memory testing.
         */
        $columnType = strtolower(Schema::getColumnType('ba_incidents', 'status'));

        $this->assertNotSame('enum', $columnType, 'ba_incidents.status must not be a native database ENUM.');
        $this->assertContains($columnType, ['string', 'varchar', 'text']);
    }
}
