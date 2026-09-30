<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\KpiSetting;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\PointTransaction;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\Video;
use App\Services\KpiContributionCalculator;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoContributionTest extends TestCase
{
    use RefreshDatabase;

    protected Division $divisionProduksi;

    protected Division $divisionEngineering;

    protected User $admin;

    protected User $supervisorProduksi;

    protected User $supervisorEngineering;

    protected User $quality;

    protected User $employeeProduksi;

    protected User $employeeEngineering;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->divisionProduksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->divisionEngineering = Division::where('name', 'Engineering')->firstOrFail();

        $this->admin = User::factory()->create(['division_id' => $this->divisionProduksi->id]);
        $this->admin->assignRole('admin');

        $this->supervisorProduksi = User::factory()->create(['division_id' => $this->divisionProduksi->id]);
        $this->supervisorProduksi->assignRole('supervisor');

        $this->supervisorEngineering = User::factory()->create(['division_id' => $this->divisionEngineering->id]);
        $this->supervisorEngineering->assignRole('supervisor');

        $this->quality = User::factory()->create(['division_id' => $this->divisionEngineering->id]);
        $this->quality->assignRole('quality');

        $this->employeeProduksi = User::factory()->create(['division_id' => $this->divisionProduksi->id]);
        $this->employeeProduksi->assignRole('employee');

        $this->employeeEngineering = User::factory()->create(['division_id' => $this->divisionEngineering->id]);
        $this->employeeEngineering->assignRole('employee');
    }

    /**
     * Test 6 (aturan KPI owner): lulus post-test video tetap menambah XP tetapi TIDAK dihitung
     * KPI Contribution, sedangkan lulus post-test materi Learning 100% DIHITUNG (1 dari 5 materi).
     */
    public function test_passing_video_post_test_awards_xp_but_only_learning_material_counts_for_kpi(): void
    {
        KpiSetting::create([
            'period_start' => now()->startOfYear()->toDateString(),
            'period_end' => now()->endOfYear()->toDateString(),
            'target_materials' => 5,
        ]);

        // 1. Buat Video yang sudah published dan punya Quiz Post-Test
        $video1 = Video::create([
            'title' => 'Video SOP Mesin CNC',
            'video_url' => 'https://youtu.be/cnc-sop',
            'division_id' => $this->divisionProduksi->id,
            'created_by' => $this->employeeProduksi->id,
            'creation_reason' => 'voluntary_improvement',
            'status' => 'published',
        ]);

        $videoQuiz = Quiz::create([
            'title' => 'Post-Test: Video SOP Mesin CNC',
            'type' => 'post_test',
            'related_type' => 'video',
            'related_id' => $video1->id,
            'points_reward' => 25,
        ]);

        // 2. Buat Learning Material biasa dan kuis post-test-nya
        $category = LearningCategory::create([
            'name' => 'Kategori Safety Umum',
            'created_by' => $this->admin->id,
        ]);

        $learningMaterial = LearningMaterial::create([
            'learning_category_id' => $category->id,
            'title' => 'Modul Pengetahuan Dasar Keselamatan',
            'type' => 'dokumen',
            'content_url' => 'https://storage/doc.pdf',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        $learningQuiz = Quiz::create([
            'title' => 'Post-Test: Modul Pengetahuan Dasar',
            'type' => 'post_test',
            'related_type' => 'learning_material',
            'related_id' => $learningMaterial->id,
            'points_reward' => 30,
        ]);

        $calculator = app(KpiContributionCalculator::class);

        // Kondisi Awal: 0
        $kpiAwal = $calculator->calculate($this->employeeEngineering);
        $this->assertEquals(0, $kpiAwal['completed']);
        $this->assertEquals(0, $kpiAwal['percentage']);

        // A. Karyawan Engineering LULUS Post-Test Video 1
        QuizAttempt::create([
            'quiz_id' => $videoQuiz->id,
            'user_id' => $this->employeeEngineering->id,
            'score' => 100,
            'passed' => true,
            'points_earned' => 25,
            'attempted_at' => now(),
        ]);
        PointTransaction::create([
            'user_id' => $this->employeeEngineering->id,
            'ledger_type' => 'xp',
            'points' => 25,
            'source_type' => 'post_test_passed',
            'source_id' => $videoQuiz->id,
            'description' => 'Lulus kuis video',
            'created_at' => now(),
        ]);

        // Cek KPI: post-test video TIDAK dihitung (tetap 0 dari 5)
        $kpiSetelahVideo = $calculator->calculate($this->employeeEngineering);
        $this->assertEquals(0, $kpiSetelahVideo['completed']);
        $this->assertEquals(0, $kpiSetelahVideo['percentage']);

        // B. Karyawan Engineering LULUS Post-Test Learning Material biasa
        QuizAttempt::create([
            'quiz_id' => $learningQuiz->id,
            'user_id' => $this->employeeEngineering->id,
            'score' => 100,
            'passed' => true,
            'points_earned' => 30,
            'attempted_at' => now(),
        ]);
        PointTransaction::create([
            'user_id' => $this->employeeEngineering->id,
            'ledger_type' => 'xp',
            'points' => 30,
            'source_type' => 'post_test_passed',
            'source_id' => $learningQuiz->id,
            'description' => 'Lulus kuis learning material',
            'created_at' => now(),
        ]);

        // Poin bertambah (25 + 30 = 55)
        $this->assertEquals(55, (int) $this->employeeEngineering->fresh()->xp);

        // KPI Contribution: materi Learning lulus 100% dihitung (1 dari 5 = 20%)
        $kpiSetelahLearning = $calculator->calculate($this->employeeEngineering);
        $this->assertEquals(1, $kpiSetelahLearning['completed']);
        $this->assertEquals(20, $kpiSetelahLearning['percentage']);
        $this->assertSame('1 dari 5 materi', $kpiSetelahLearning['summary']);
    }
}
