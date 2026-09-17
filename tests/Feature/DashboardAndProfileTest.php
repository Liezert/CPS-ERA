<?php

namespace Tests\Feature;

use App\Models\BaIncident;
use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\PointTransaction;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserLearningProgress;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardAndProfileTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->division = Division::firstOrFail();

        $this->user = User::factory()->create([
            'name' => 'Kurniawan Pratama',
            'division_id' => $this->division->id,
            'total_points' => 2500,
            'level' => 2,
            'jabatan' => 'Staff Quality',
        ]);
        $this->user->assignRole('employee');
    }

    /**
     * Checklist 1: Endpoint GET /api/dashboard/summary mengembalikan seluruh field yang dispesifikasikan.
     */
    public function test_dashboard_summary_returns_all_required_aggregated_fields(): void
    {
        // 1. Buat dokumen oleh user
        KnowledgeDocument::create([
            'title' => 'Dokumen Standar QC 1',
            'division_id' => $this->division->id,
            'type' => 'sop',
            'description' => 'Deskripsi standar QC',
            'created_by' => $this->user->id,
            'status' => 'published',
        ]);

        // 2. Buat BA dan lesson learned terkait user
        $ba = BaIncident::create([
            'nomor_ba' => 'BA-2026-0099',
            'division_id' => $this->division->id,
            'file_ba_url' => 'https://example.com/ba.pdf',
            'file_ftk_url' => 'https://example.com/ftk.pdf',
            'status' => 'reviewed',
            'created_by' => $this->user->id,
        ]);

        KnowledgeDocument::create([
            'title' => 'Lesson Learned: Penanganan Cacat Produk',
            'division_id' => $this->division->id,
            'type' => 'lesson_learned',
            'description' => 'Lesson learned dari insiden QC',
            'source_ba_id' => $ba->id,
            'created_by' => $this->user->id,
            'status' => 'published',
        ]);

        // 3. Buat kategori & materi pembelajaran dengan progres belajar
        $category = LearningCategory::create([
            'name' => 'Metrologi Dasar',
            'created_by' => $this->user->id,
        ]);

        $material1 = LearningMaterial::create([
            'learning_category_id' => $category->id,
            'title' => 'Pengenalan Kaliper',
            'type' => 'video',
            'content_url' => 'https://youtube.com/watch?v=sample',
            'description' => 'Video pengenalan kaliper',
            'status' => 'published',
            'created_by' => $this->user->id,
        ]);

        $material2 = LearningMaterial::create([
            'learning_category_id' => $category->id,
            'title' => 'Pengenalan Mikrometer',
            'type' => 'dokumen',
            'content_url' => 'https://example.com/doc.pdf',
            'description' => 'Dokumen pengenalan mikrometer',
            'status' => 'published',
            'created_by' => $this->user->id,
        ]);

        UserLearningProgress::create([
            'user_id' => $this->user->id,
            'learning_material_id' => $material1->id,
            'progress_percent' => 80,
        ]);

        UserLearningProgress::create([
            'user_id' => $this->user->id,
            'learning_material_id' => $material2->id,
            'progress_percent' => 40,
        ]);

        // 4. Buat quiz misi yang belum dikerjakan
        Quiz::create([
            'title' => 'Misi Pengetahuan Kaizen 1',
            'type' => 'mission_quiz',
            'points_reward' => 50,
            'description' => 'Misi dasar kaizen',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/summary');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'knowledge_documents_count',
                'lesson_learned_count',
                'average_learning_progress',
                'kpi_contribution_percent',
                'kpi_contribution_status',
                'level',
                'total_points',
                'points_to_next_level',
                'next_level_threshold',
                'level_progress_percent',
                'unfinished_learning_materials',
                'unfinished_missions',
                'latest_knowledge_documents',
            ],
        ]);

        $data = $response->json('data');

        // Jumlah dokumen buatan user = 2 (SOP + lesson learned)
        $this->assertSame(2, $data['knowledge_documents_count']);
        // Jumlah lesson learned terkait user = 1
        $this->assertSame(1, $data['lesson_learned_count']);
        // Rata-rata progres belajar = (80 + 40) / 2 = 60.0%
        $this->assertSame(60.0, (float) $data['average_learning_progress']);

        // Level & Poin (total 2500 poin => Level 2, next threshold 4000, points to next level 1500, progress 25%)
        $this->assertSame(2, $data['level']);
        $this->assertSame(2500, $data['total_points']);
        $this->assertSame(1500, $data['points_to_next_level']);
        $this->assertSame(4000, $data['next_level_threshold']);
        $this->assertSame(25, $data['level_progress_percent']);

        // Unfinished materials max 3
        $this->assertNotEmpty($data['unfinished_learning_materials']);
        $this->assertLessThanOrEqual(3, count($data['unfinished_learning_materials']));

        // Unfinished missions max 3
        $this->assertNotEmpty($data['unfinished_missions']);
        $this->assertLessThanOrEqual(3, count($data['unfinished_missions']));

        // Latest knowledge documents max 3
        $this->assertNotEmpty($data['latest_knowledge_documents']);
        $this->assertLessThanOrEqual(3, count($data['latest_knowledge_documents']));
    }

    /**
     * Checklist 2: Field KPI Contribution % sekarang terisi nilai nyata dan berstatus active (Video Contribution Tahap Lanjutan).
     */
    public function test_kpi_contribution_percent_is_active_and_computed(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/dashboard/summary');

        $response->assertOk();
        $this->assertIsInt($response->json('data.kpi_contribution_percent'));
        $this->assertSame('active', $response->json('data.kpi_contribution_status'));
    }

    /**
     * Checklist 3: Tidak ada N+1 query pada endpoint dashboard (memakai eager loading).
     */
    public function test_dashboard_summary_does_not_have_n_plus_one_queries(): void
    {
        $category = LearningCategory::create([
            'name' => 'Kategori Uji N+1',
            'created_by' => $this->user->id,
        ]);

        // Buat 5 materi belajar dan 5 knowledge dokumen
        for ($i = 1; $i <= 5; $i++) {
            $mat = LearningMaterial::create([
                'learning_category_id' => $category->id,
                'title' => "Materi {$i}",
                'type' => 'dokumen',
                'content_url' => 'https://example.com/doc.pdf',
                'description' => 'Deskripsi',
                'status' => 'published',
                'created_by' => $this->user->id,
            ]);

            UserLearningProgress::create([
                'user_id' => $this->user->id,
                'learning_material_id' => $mat->id,
                'progress_percent' => $i * 15,
            ]);

            KnowledgeDocument::create([
                'title' => "Dokumen {$i}",
                'division_id' => $this->division->id,
                'type' => 'dokumen',
                'description' => 'Deskripsi',
                'created_by' => $this->user->id,
                'status' => 'published',
            ]);

            Quiz::create([
                'title' => "Misi {$i}",
                'type' => 'mission_quiz',
                'points_reward' => 20,
                'description' => 'Deskripsi',
            ]);
        }

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $response = $this->actingAs($this->user)->getJson('/api/dashboard/summary');
        $response->assertOk();

        // Jumlah query harus terkontrol dan konstan (sekitar 8-12 query total, bukan 50+)
        $this->assertLessThanOrEqual(15, $queryCount);
    }

    /**
     * Checklist 4: GET /api/me/profile mengembalikan data profil + grafik performa 6 bulan terakhir.
     */
    public function test_get_profile_returns_user_details_and_six_months_performance_chart(): void
    {
        // Masukkan transaksi poin pada bulan berjalan dan bulan-bulan sebelumnya
        PointTransaction::create([
            'user_id' => $this->user->id,
            'points' => 300,
            'source_type' => 'mission_completed',
            'ledger_type' => 'xp',
            'description' => 'Bulan ini',
            'created_at' => now(),
        ]);

        PointTransaction::create([
            'user_id' => $this->user->id,
            'points' => 500,
            'source_type' => 'post_test_passed',
            'ledger_type' => 'xp',
            'description' => 'Bulan lalu',
            'created_at' => now()->subMonth(),
        ]);

        PointTransaction::create([
            'user_id' => $this->user->id,
            'points' => 700,
            'source_type' => 'ba_submission',
            'ledger_type' => 'xp',
            'description' => 'Dua bulan lalu',
            'created_at' => now()->subMonths(2),
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/me/profile');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'email',
                'employee_id',
                'jabatan',
                'avatar_url',
                'division' => ['id', 'name'],
                'roles',
                'level',
                'total_points',
                'points_to_next_level',
                'next_level_threshold',
                'level_progress_percent',
                'average_learning_progress',
                'kpi_contribution_percent',
                'monthly_performance' => [
                    '*' => ['month', 'label', 'points'],
                ],
            ],
        ]);

        $monthly = $response->json('data.monthly_performance');

        // Harus tepat 6 bulan
        $this->assertCount(6, $monthly);

        // Bulan terakhir dalam array adalah bulan berjalan
        $currentMonthEntry = end($monthly);
        $this->assertSame(now()->format('Y-m'), $currentMonthEntry['month']);
        $this->assertSame(300, $currentMonthEntry['points']);
    }

    /**
     * Checklist 4 (lanjutan): PATCH /api/me/profile memperbarui data profil dengan benar.
     */
    public function test_patch_profile_updates_allowed_fields(): void
    {
        $payload = [
            'name' => 'Kurniawan Pratama Updated',
            'jabatan' => 'Senior Quality Specialist',
            'avatar_url' => 'https://example.com/new-avatar.png',
        ];

        $response = $this->actingAs($this->user)->patchJson('/api/me/profile', $payload);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'Kurniawan Pratama Updated');
        $response->assertJsonPath('data.jabatan', 'Senior Quality Specialist');
        $response->assertJsonPath('data.avatar_url', 'https://example.com/new-avatar.png');

        $this->user->refresh();
        $this->assertSame('Kurniawan Pratama Updated', $this->user->name);
        $this->assertSame('Senior Quality Specialist', $this->user->jabatan);
        $this->assertSame('https://example.com/new-avatar.png', $this->user->avatar_url);
    }
}
