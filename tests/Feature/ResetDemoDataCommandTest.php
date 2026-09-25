<?php

namespace Tests\Feature;

use App\Models\BaActivityLog;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\KnowledgeTopic;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\Notification;
use App\Models\PointTransaction;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\DemoStarterSeeder;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * `cps:reset-demo-data`: buang data transaksi dummy, pertahankan data master, lalu isi data starter.
 */
class ResetDemoDataCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    private function seedDummyData(): void
    {
        $division = Division::where('name', 'Produksi')->firstOrFail();

        $dummy = User::factory()->create(['email' => 'admin@cps.test', 'division_id' => $division->id]);
        $dummy->assignRole('admin');
        $other = User::factory()->create(['division_id' => $division->id]);
        $other->assignRole('employee');

        $incident = BaIncident::create([
            'nomor_ba' => 'BA-2026-0001',
            'title' => 'Insiden dummy',
            'division_id' => $division->id,
            'deskripsi_masalah' => 'Dummy',
            'status' => 'draft',
            'created_by' => $other->id,
        ]);
        BaActivityLog::create(['ba_incident_id' => $incident->id, 'actor_id' => $other->id, 'action' => 'Draft BA dibuat']);

        Notification::create(['user_id' => $other->id, 'type' => 'ba_review', 'title' => 'Dummy', 'message' => 'Dummy']);
        PointTransaction::create(['user_id' => $other->id, 'ledger_type' => PointTransaction::LEDGER_XP, 'points' => 10, 'source_type' => 'admin_adjustment', 'description' => 'Dummy']);

        $category = LearningCategory::create(['name' => 'Kategori Dummy', 'created_by' => $dummy->id]);
        LearningMaterial::create([
            'id' => (string) Str::uuid(),
            'learning_category_id' => $category->id,
            'title' => 'Materi Dummy',
            'type' => 'artikel',
            'status' => 'published',
            'created_by' => $dummy->id,
        ]);

        KnowledgeTopic::create(['name' => 'Topik Master', 'created_by' => $dummy->id]);

        Quiz::create([
            'id' => (string) Str::uuid(),
            'title' => 'Misi Dummy',
            'type' => 'mission_quiz',
            'related_type' => 'none',
            'points_reward' => 10,
        ]);
    }

    public function test_command_purges_dummy_data_and_seeds_clean_starter_data(): void
    {
        $this->seedDummyData();

        $this->artisan('cps:reset-demo-data', ['--force' => true])->assertSuccessful();

        // Data transaksi dummy hilang.
        $this->assertSame(0, BaIncident::count(), 'BaIncident');
        $this->assertSame(0, BaActivityLog::count(), 'BaActivityLog');
        $this->assertSame(0, Notification::count(), 'Notification');
        $this->assertSame(0, PointTransaction::count(), 'PointTransaction');
        $this->assertSame(0, DB::table('achievements')->count(), 'achievements');
        $this->assertSame(0, DB::table('quiz_attempts')->count(), 'quiz_attempts');
        $this->assertSame(0, LearningMaterial::where('title', 'Materi Dummy')->count());
        $this->assertSame(0, Quiz::where('title', 'Misi Dummy')->count());

        // Data master utuh.
        $this->assertSame(12, Division::count());
        $this->assertSame(4, DB::table('roles')->count());
        $this->assertSame(1, KnowledgeTopic::where('name', 'Topik Master')->count());

        // Akun uji bersih: hanya 3, semuanya wajib ganti kata sandi.
        $this->assertSame(3, User::count());
        $admin = User::where('email', 'admin@cps.test')->firstOrFail();
        $employee = User::where('email', 'employee@cps.test')->firstOrFail();
        $supervisor = User::where('email', 'supervisor@cps.test')->firstOrFail();

        foreach ([$admin, $employee, $supervisor] as $user) {
            $this->assertTrue(Hash::check(DemoStarterSeeder::DEMO_PASSWORD, $user->password));
            $this->assertTrue($user->must_change_password);
            $this->assertTrue($user->hasVerifiedEmail());
        }

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($employee->hasRole('employee'));
        $this->assertTrue($supervisor->hasRole('supervisor'));
        $this->assertSame('ADM-001', $admin->employee_id);
        $this->assertSame(Division::HRGA, $admin->division->name);

        // Supervisor sedivisi dengan employee supaya alur approval CAPA terhubung.
        $this->assertSame($employee->division_id, $supervisor->division_id);
        $this->assertSame('Produksi', $employee->division->name);

        // Master yang tadinya dimiliki user dummy dipindah ke admin demo, bukan ikut terhapus.
        $this->assertSame($admin->id, KnowledgeTopic::where('name', 'Topik Master')->value('created_by'));
        $this->assertSame(0, LearningCategory::where('created_by', '!=', $admin->id)->count());

        // Tidak ada sisa pivot role milik user yang sudah dihapus.
        $this->assertSame(
            0,
            DB::table('model_has_roles')->whereNotIn('model_id', [$admin->id, $employee->id, $supervisor->id])->count()
        );
    }

    public function test_command_seeds_three_learning_materials_and_three_missions(): void
    {
        $this->artisan('cps:reset-demo-data', ['--force' => true])->assertSuccessful();

        $this->assertSame(3, LearningMaterial::count());
        foreach ([
            'Penerapan Budaya 5S (Ringkas, Rapi, Resik, Rawat, Rajin) di Lantai Produksi' => 'K3 & Lingkungan Kerja',
            'Prosedur Keselamatan Pengoperasian Mesin & APD Wajib' => 'SOP & K3',
            'Standar Identifikasi Cacat Produk & Alur Pelaporan CAPA' => 'Quality Control & Improvement',
        ] as $title => $category) {
            $material = LearningMaterial::where('title', $title)->firstOrFail();
            $this->assertSame($category, $material->category->name);
            $this->assertSame('published', $material->status);
        }

        $missions = Quiz::missions()->get();
        $this->assertCount(3, $missions);
        $this->assertSame([30, 40, 50], $missions->pluck('points_reward')->sort()->values()->all());

        // Tiap misi bisa diselesaikan: punya pertanyaan dengan satu jawaban benar.
        foreach ($missions as $mission) {
            $question = $mission->questions()->firstOrFail();
            $this->assertSame(1, $question->options()->where('is_correct', true)->count());
            $this->assertSame(3, $question->options()->count());
        }
    }

    public function test_command_is_idempotent_when_run_twice(): void
    {
        $this->artisan('cps:reset-demo-data', ['--force' => true])->assertSuccessful();
        $this->artisan('cps:reset-demo-data', ['--force' => true])->assertSuccessful();

        $this->assertSame(3, User::count());
        $this->assertSame(3, LearningMaterial::count());
        $this->assertSame(3, Quiz::missions()->count());
        $this->assertSame(12, Division::count());
    }
}
