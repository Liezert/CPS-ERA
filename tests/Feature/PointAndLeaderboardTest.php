<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\PointTransaction;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointAndLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected Division $divisionA;

    protected Division $divisionB;

    protected User $user1;

    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $divisions = Division::take(2)->get();
        $this->divisionA = $divisions[0];
        $this->divisionB = $divisions[1];

        $this->user1 = User::factory()->create([
            'name' => 'Andi Wijaya',
            'division_id' => $this->divisionA->id,
            'xp' => 0,
        ]);
        $this->user1->assignRole('employee');

        $this->user2 = User::factory()->create([
            'name' => 'Budi Pratama',
            'division_id' => $this->divisionB->id,
            'xp' => 0,
        ]);
        $this->user2->assignRole('employee');
    }

    /**
     * Checklist 1: Insert manual ke point_transactions langsung mengubah users.xp
     * via Model Observer (bukan manual update di controller).
     */
    public function test_insert_point_transaction_automatically_updates_user_xp_via_observer(): void
    {
        $this->assertSame(0, $this->user1->xp);

        // Insert transaksi poin 1
        PointTransaction::create([
            'user_id' => $this->user1->id,
            'ledger_type' => 'xp',
            'points' => 150,
            'source_type' => 'mission_completed',
            'description' => 'Menyelesaikan misi modul 1',
            'created_at' => now(),
        ]);

        $this->assertSame(150, $this->user1->fresh()->xp);

        // Insert transaksi poin 2
        PointTransaction::create([
            'user_id' => $this->user1->id,
            'ledger_type' => 'xp',
            'points' => 350,
            'source_type' => 'post_test_passed',
            'description' => 'Lulus post test',
            'created_at' => now(),
        ]);

        $this->assertSame(500, $this->user1->fresh()->xp);
    }

    /**
     * Checklist 3: xp selalu sinkron dengan SUM ledger setelah beberapa insert/koreksi
     * (termasuk insert poin negatif untuk admin_adjustment).
     */
    public function test_xp_stays_in_sync_after_multiple_inserts_and_negative_admin_adjustment(): void
    {
        // Transaksi positif beruntun
        PointTransaction::create([
            'user_id' => $this->user1->id,
            'ledger_type' => 'xp',
            'points' => 1000,
            'source_type' => 'mission_completed',
            'description' => 'Poin awal',
            'created_at' => now(),
        ]);

        PointTransaction::create([
            'user_id' => $this->user1->id,
            'ledger_type' => 'xp',
            'points' => 2000,
            'source_type' => 'ba_reviewed',
            'description' => 'BA approved',
            'created_at' => now(),
        ]);

        $this->assertSame(3000, $this->user1->fresh()->xp);

        // Koreksi negatif oleh admin (admin_adjustment)
        PointTransaction::create([
            'user_id' => $this->user1->id,
            'ledger_type' => 'xp',
            'points' => -500,
            'source_type' => 'admin_adjustment',
            'description' => 'Koreksi kelebihan poin oleh admin',
            'created_at' => now(),
        ]);

        $expectedSum = (int) PointTransaction::where('user_id', $this->user1->id)->sum('points');
        $this->assertSame(2500, $expectedSum);
        $this->assertSame($expectedSum, $this->user1->fresh()->xp);
    }
}
