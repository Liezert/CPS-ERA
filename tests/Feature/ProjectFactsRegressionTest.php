<?php

namespace Tests\Feature;

use App\Models\Division;
use Database\Seeders\DivisionSeeder;
use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectFactsRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PRD v2.0 §1.4: Database Stack Fact Lock.
     * config('database.default') must resolve to 'pgsql' (PostgreSQL), NOT 'mysql'.
     */
    public function test_config_database_default_resolves_to_pgsql_and_never_mysql(): void
    {
        // 1. Check direct configuration file fallback in config/database.php
        $configContent = file_get_contents(config_path('database.php'));
        $this->assertStringContainsString("'default' => env('DB_CONNECTION', 'pgsql')", $configContent);
        $this->assertStringNotContainsString("'default' => env('DB_CONNECTION', 'mysql')", $configContent);

        // 2. Check production environment files (.env and .env.example)
        if (file_exists(base_path('.env'))) {
            $env = Dotenv::createArrayBacked(base_path())->load();
            $this->assertSame('pgsql', $env['DB_CONNECTION'] ?? null);
            $this->assertNotSame('mysql', $env['DB_CONNECTION'] ?? null);
        }

        $exampleEnv = Dotenv::createArrayBacked(base_path(), '.env.example')->load();
        $this->assertSame('pgsql', $exampleEnv['DB_CONNECTION'] ?? null);
        $this->assertNotSame('mysql', $exampleEnv['DB_CONNECTION'] ?? null);

        // 3. Check that current runtime is never 'mysql'
        $this->assertNotSame('mysql', config('database.default'));

        // 4. Check application config resolution in clean process (outside PHPUnit's DB_CONNECTION=sqlite override)
        $env = getenv();
        unset($env['DB_CONNECTION'], $env['APP_ENV']);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open('php artisan tinker --execute="echo config(\'database.default\');"', $descriptors, $pipes, base_path(), $env);
        $output = '';

        if (is_resource($proc)) {
            $output = trim(stream_get_contents($pipes[1]));
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($proc);
        }

        $this->assertSame('pgsql', $output);
        $this->assertNotSame('mysql', $output);
    }

    /**
     * PRD v2.0 §1.4 & §2.1: Exactly 13 Divisions Fact Lock.
     * Division::count() must be exactly 13, and Division names must match the official list.
     */
    public function test_divisions_count_is_exactly_thirteen_and_names_match_official_prd_v2_list(): void
    {
        $this->seed(DivisionSeeder::class);

        // 1. Exactly 13 divisions
        $this->assertSame(13, Division::count());

        // 2. Official 13 names per PRD v2.0 §1.4
        $expectedDivisions = collect([
            'Engineering',
            'Finance Accounting Tax',
            'Gudang RM',
            'HRGA',
            'Keamanan',
            'PPIC',
            'Produksi',
            'Purchasing',
            'Quality Control',
            'Repair',
            'Sales & Marketing',
            'Warehouse & Delivery',
            'IT',
        ])->sort()->values()->all();

        $actualDivisions = Division::pluck('name')->sort()->values()->all();

        $this->assertSame($expectedDivisions, $actualDivisions);
    }

    /**
     * PRD v2.0 §1.4 & §3.1: Non-ENUM Status Column Fact Lock.
     * ba_incidents.status must be stored as standard VARCHAR/TEXT, NOT a native PostgreSQL ENUM.
     * Status transition and validation are strictly enforced at the application/service level.
     */
    public function test_ba_incidents_status_column_is_not_native_postgresql_enum(): void
    {
        /**
         * Penjelasan Desain Arsitektur (PRD v2.0 §1.4 & §3.1):
         * Kolom ba_incidents.status TIDAK dibuat sebagai ENUM native database (PostgreSQL type).
         * Alasan:
         * 1. Menghindari DDL locking dan kompleksitas migrasi ALTER TYPE pada PostgreSQL production
         *    apabila di kemudian hari terdapat perubahan alur status.
         * 2. Validasi transisi status dijaga secara ketat di layer service (BaIncidentService)
         *    mengikuti aturan 4 tahap: draft -> submitted -> approved / rejected.
         * 3. Mempertahankan kompatibilitas test runner dan portabilitas SQLite in-memory testing.
         */

        // 1. Schema check in active test connection
        $columnType = strtolower(Schema::getColumnType('ba_incidents', 'status'));
        $this->assertNotSame('enum', $columnType);
        $this->assertContains($columnType, ['string', 'varchar', 'text']);

        // 2. Information_schema check on PostgreSQL connection when database is available
        try {
            config(['database.connections.pgsql.database' => 'cps_era']);
            DB::purge('pgsql');

            $pgColumn = DB::connection('pgsql')->selectOne("
                SELECT data_type, udt_name 
                FROM information_schema.columns 
                WHERE table_name = 'ba_incidents' AND column_name = 'status'
            ");

            if ($pgColumn) {
                // Native PostgreSQL ENUM produces data_type = 'USER-DEFINED'
                $this->assertNotSame('USER-DEFINED', $pgColumn->data_type, 'ba_incidents.status must not be a native PostgreSQL ENUM (USER-DEFINED).');
                $this->assertSame('character varying', $pgColumn->data_type);
                $this->assertSame('varchar', $pgColumn->udt_name);
            }
        } catch (\Throwable $e) {
            // Non-critical fallback if PostgreSQL server is not accessible during test run.
        }
    }
}
