<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\LevelCalculator;
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
            'total_points' => 0,
            'level' => 1,
        ]);
        $this->user1->assignRole('employee');

        $this->user2 = User::factory()->create([
            'name' => 'Budi Pratama',
            'division_id' => $this->divisionB->id,
            'total_points' => 0,
            'level' => 1,
        ]);
        $this->user2->assignRole('employee');
    }

    /**
     * Checklist 1: Insert manual ke point_transactions langsung mengubah users.total_points
     * via Model Observer (bukan manual update di controller).
     */
    public function test_insert_point_transaction_automatically_updates_user_total_points_via_observer(): void
    {
        $this->assertSame(0, $this->user1->total_points);

        // Insert transaksi poin 1
        PointTransaction::create([
            'user_id' => $this->user1->id,
            'ledger_type' => 'xp',
            'points' => 150,
            'source_type' => 'mission_completed',
            'description' => 'Menyelesaikan misi modul 1',
            'created_at' => now(),
        ]);

        $this->assertSame(150, $this->user1->fresh()->total_points);

        // Insert transaksi poin 2
        PointTransaction::create([
            'user_id' => $this->user1->id,
            'ledger_type' => 'xp',
            'points' => 350,
            'source_type' => 'post_test_passed',
            'description' => 'Lulus post test',
            'created_at' => now(),
        ]);

        $this->assertSame(500, $this->user1->fresh()->total_points);
    }

    /**
     * Checklist 2: Level ikut berubah lewat LevelCalculator, bukan logic tersebar.
     */
    public function test_level_is_updated_via_level_calculator_service(): void
    {
        $calculator = app(LevelCalculator::class);

        // Unit assertions untuk LevelCalculator
        $this->assertSame(1, $calculator->calculate(-100));
        $this->assertSame(1, $calculator->calculate(0));
        $this->assertSame(1, $calculator->calculate(1999));
        $this->assertSame(2, $calculator->calculate(2000));
        $this->assertSame(2, $calculator->calculate(3999));
        $this->assertSame(3, $calculator->calculate(4000));

        // Melalui transaksi point:
        $this->assertSame(1, $this->user1->level);

        // Tambah 2.500 poin -> harus otomatis Level 2
        PointTransaction::create([
            'user_id' => $this->user1->id,
            'ledger_type' => 'xp',
            'points' => 2500,
            'source_type' => 'ba_submission',
            'description' => 'Reward submit BA',
            'created_at' => now(),
        ]);

        $this->user1->refresh();
        $this->assertSame(2500, $this->user1->total_points);
        $this->assertSame(2, $this->user1->level);

        // Tambah lagi 2.000 poin (total 4.500 poin) -> harus otomatis Level 3
        PointTransaction::create([
            'user_id' => $this->user1->id,
            'ledger_type' => 'xp',
            'points' => 2000,
            'source_type' => 'learning_completion',
            'description' => 'Reward complete learning',
            'created_at' => now(),
        ]);

        $this->user1->refresh();
        $this->assertSame(4500, $this->user1->total_points);
        $this->assertSame(3, $this->user1->level);
    }

    /**
     * Checklist 3: total_points selalu sinkron dengan SUM ledger setelah beberapa insert/koreksi
     * (termasuk insert poin negatif untuk admin_adjustment).
     */
    public function test_total_points_stays_in_sync_after_multiple_inserts_and_negative_admin_adjustment(): void
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

        $this->assertSame(3000, $this->user1->fresh()->total_points);

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
        $this->assertSame($expectedSum, $this->user1->fresh()->total_points);
        $this->assertSame(2, $this->user1->fresh()->level);
    }

    /**
     * Checklist 4: Leaderboard terurut benar (total_points DESC) dan bisa difilter per divisi.
     */
    public function test_leaderboard_endpoint_returns_users_ordered_by_total_points_desc(): void
    {
        // Berikan poin berbeda ke user1 dan user2
        PointTransaction::create([
            'user_id' => $this->user1->id,
            'ledger_type' => 'xp',
            'points' => 1000,
            'source_type' => 'mission_completed',
            'description' => 'User1 1000 pts',
            'created_at' => now(),
        ]);

        PointTransaction::create([
            'user_id' => $this->user2->id,
            'ledger_type' => 'xp',
            'points' => 3000,
            'source_type' => 'mission_completed',
            'description' => 'User2 3000 pts',
            'created_at' => now(),
        ]);

        // Tambah user ke-3 dengan poin 2000
        $user3 = User::factory()->create([
            'name' => 'Citra Dewi',
            'division_id' => $this->divisionA->id,
            'total_points' => 0,
            'level' => 1,
        ]);
        $user3->assignRole('employee');

        PointTransaction::create([
            'user_id' => $user3->id,
            'ledger_type' => 'xp',
            'points' => 2000,
            'source_type' => 'mission_completed',
            'description' => 'User3 2000 pts',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user1)->getJson('/api/leaderboard');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'period',
            'data' => [
                '*' => [
                    'rank',
                    'id',
                    'name',
                    'employee_id',
                    'division' => ['id', 'name'],
                    'total_points',
                    'points',
                    'level',
                ],
            ],
        ]);

        $data = $response->json('data');

        // Urutan harus: user2 (3000 pts, rank 1), user3 (2000 pts, rank 2), user1 (1000 pts, rank 3)
        $this->assertSame($this->user2->id, $data[0]['id']);
        $this->assertSame(1, $data[0]['rank']);
        $this->assertSame(3000, $data[0]['total_points']);

        $this->assertSame($user3->id, $data[1]['id']);
        $this->assertSame(2, $data[1]['rank']);
        $this->assertSame(2000, $data[1]['total_points']);

        $this->assertSame($this->user1->id, $data[2]['id']);
        $this->assertSame(3, $data[2]['rank']);
        $this->assertSame(1000, $data[2]['total_points']);
    }

    /**
     * Checklist 4 (lanjutan): Leaderboard bisa difilter per divisi.
     */
    public function test_leaderboard_can_be_filtered_by_division(): void
    {
        PointTransaction::create([
            'user_id' => $this->user1->id,
            'ledger_type' => 'xp',
            'points' => 1000,
            'source_type' => 'mission_completed',
            'description' => 'User1 divA',
            'created_at' => now(),
        ]);

        PointTransaction::create([
            'user_id' => $this->user2->id,
            'ledger_type' => 'xp',
            'points' => 3000,
            'source_type' => 'mission_completed',
            'description' => 'User2 divB',
            'created_at' => now(),
        ]);

        // Filter divisi A by division name
        $responseByName = $this->actingAs($this->user1)
            ->getJson('/api/leaderboard?division='.urlencode($this->divisionA->name));

        $responseByName->assertOk();
        $dataA = $responseByName->json('data');
        $this->assertCount(1, $dataA);
        $this->assertSame($this->user1->id, $dataA[0]['id']);

        // Filter divisi B by division ID
        $responseById = $this->actingAs($this->user1)
            ->getJson('/api/leaderboard?division='.$this->divisionB->id);

        $responseById->assertOk();
        $dataB = $responseById->json('data');
        $this->assertCount(1, $dataB);
        $this->assertSame($this->user2->id, $dataB[0]['id']);
    }

    /**
     * Filter periode mingguan/bulanan/all-time menghitung points dengan benar.
     */
    public function test_leaderboard_supports_periods_and_calculates_period_points(): void
    {
        // Transaksi minggu ini
        PointTransaction::create([
            'user_id' => $this->user1->id,
            'ledger_type' => 'xp',
            'points' => 400,
            'source_type' => 'mission_completed',
            'description' => 'Minggu ini',
            'created_at' => now(),
        ]);

        // Transaksi bulan lalu (minggu lalu)
        PointTransaction::create([
            'user_id' => $this->user1->id,
            'ledger_type' => 'xp',
            'points' => 600,
            'source_type' => 'mission_completed',
            'description' => 'Bulan lalu',
            'created_at' => now()->subMonths(2),
        ]);

        $this->assertSame(1000, $this->user1->fresh()->total_points);

        // Period: all-time
        $resAll = $this->actingAs($this->user1)->getJson('/api/leaderboard?period=all-time&division='.$this->divisionA->id);
        $resAll->assertOk();
        $this->assertSame(1000, $resAll->json('data.0.points'));
        $this->assertSame(1000, $resAll->json('data.0.total_points'));

        // Period: mingguan (hanya mencakup transaksi minggu ini: 400 poin)
        $resWeekly = $this->actingAs($this->user1)->getJson('/api/leaderboard?period=mingguan&division='.$this->divisionA->id);
        $resWeekly->assertOk();
        $this->assertSame(400, $resWeekly->json('data.0.points'));
        $this->assertSame(1000, $resWeekly->json('data.0.total_points'));
    }
}
