<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Keputusan owner 2026-09-24: Marketing dan Sales digabung jadi "Marketing & Sales" (12 divisi total).
 */
class MarketingSalesDivisionMergeTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): object
    {
        return require database_path('migrations/2026_09_24_000003_merge_marketing_and_sales_divisions.php');
    }

    public function test_merge_moves_sales_records_into_marketing_and_rollback_restores_them(): void
    {
        // Mulai dari skema sebelum merge dengan dua divisi terpisah.
        $migration = $this->migration();
        $migration->down();

        $marketing = Division::create(['name' => 'Marketing']);
        $sales = Division::create(['name' => 'Sales']);
        $salesUser = User::factory()->create(['division_id' => $sales->id]);
        $marketingUser = User::factory()->create(['division_id' => $marketing->id]);

        $migration->up();

        $this->assertNull(Division::where('name', 'Sales')->first());
        $this->assertNull(Division::where('name', 'Marketing')->first());
        $this->assertSame('Marketing & Sales', $marketing->fresh()->name);
        $this->assertSame($marketing->id, $salesUser->fresh()->division_id);
        $this->assertSame($marketing->id, $marketingUser->fresh()->division_id);

        $migration->down();

        $this->assertSame('Marketing', $marketing->fresh()->name);
        $this->assertSame($sales->id, $salesUser->fresh()->division_id);
        $this->assertSame($marketing->id, $marketingUser->fresh()->division_id);
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('division_merge_backup'));

        $migration->up();
    }

    public function test_fresh_install_without_divisions_is_untouched(): void
    {
        $migration = $this->migration();
        $migration->down();

        $migration->up();

        $this->assertSame(0, Division::count());
    }
}
