<?php

namespace Tests\Feature;

use App\Filament\Resources\KnowledgeDocuments\KnowledgeDocumentResource;
use App\Filament\Resources\KnowledgeTopics\KnowledgeTopicResource;
use App\Filament\Resources\UserKpiYearlies\UserKpiYearlyResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserKpiYearly;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccessMatrixGateAndPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Division $divisionA;

    protected Division $divisionHrga;

    protected User $admin;

    protected User $quality;

    protected User $hrgaUser;

    protected User $supervisor;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->divisionA = Division::firstOrFail();
        $this->divisionHrga = Division::where('name', 'HRGA')->firstOrFail();

        // 1. Admin
        $this->admin = User::factory()->create([
            'division_id' => $this->divisionA->id,
            'employee_id' => 'CPS-ADM01',
        ]);
        $this->admin->assignRole('admin');

        // 2. Quality
        $this->quality = User::factory()->create([
            'division_id' => $this->divisionA->id,
            'employee_id' => 'CPS-QLT01',
        ]);
        $this->quality->assignRole('quality');

        // 3. User with HRGA division
        $this->hrgaUser = User::factory()->create([
            'division_id' => $this->divisionHrga->id,
            'employee_id' => 'CPS-HRG01',
        ]);
        $this->hrgaUser->assignRole('employee');

        // 4. Supervisor (Divisi A)
        $this->supervisor = User::factory()->create([
            'division_id' => $this->divisionA->id,
            'employee_id' => 'CPS-SPV01',
        ]);
        $this->supervisor->assignRole('supervisor');

        // 5. Employee (Divisi A)
        $this->employee = User::factory()->create([
            'division_id' => $this->divisionA->id,
            'employee_id' => 'CPS-EMP01',
        ]);
        $this->employee->assignRole('employee');
    }

    /**
     * Poin 1: Kelola Topik Knowledge Repository (buat/edit/hapus knowledge_topics):
     * HANYA role Quality/HRGA dan Admin. Gate 'manage-knowledge-topics' diterapkan di KnowledgeTopicResource.
     */
    public function test_poin_1_manage_knowledge_topics_gate_and_resource_access(): void
    {
        // Gate check
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-knowledge-topics'));
        $this->assertTrue(Gate::forUser($this->quality)->allows('manage-knowledge-topics'));
        $this->assertTrue(Gate::forUser($this->hrgaUser)->allows('manage-knowledge-topics'));
        $this->assertFalse(Gate::forUser($this->supervisor)->allows('manage-knowledge-topics'));
        $this->assertFalse(Gate::forUser($this->employee)->allows('manage-knowledge-topics'));

        // Resource static policy check
        $this->actingAs($this->admin);
        $this->assertTrue(KnowledgeTopicResource::canAccess());
        $this->assertTrue(KnowledgeTopicResource::canCreate());

        $this->actingAs($this->quality);
        $this->assertTrue(KnowledgeTopicResource::canAccess());

        $this->actingAs($this->supervisor);
        $this->assertFalse(KnowledgeTopicResource::canAccess());
        $this->assertFalse(KnowledgeTopicResource::canCreate());

        $this->actingAs($this->employee);
        $this->assertFalse(KnowledgeTopicResource::canAccess());

        // HTTP Route access in Filament panel
        $this->actingAs($this->admin)->get('/admin/knowledge-topics')->assertOk();
        $this->actingAs($this->quality)->get('/admin/knowledge-topics')->assertOk();
        $this->actingAs($this->supervisor)->get('/admin/knowledge-topics')->assertForbidden();
    }

    /**
     * Poin 2: Tambah konten Knowledge Repository baru (SOP/kebijakan/dll, bukan Lesson Learned dari BA):
     * HANYA role Quality/HRGA dan Admin via Gate 'manage-knowledge-documents'.
     */
    public function test_poin_2_manage_knowledge_documents_gate_and_policy(): void
    {
        // Gate check
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-knowledge-documents'));
        $this->assertTrue(Gate::forUser($this->quality)->allows('manage-knowledge-documents'));
        $this->assertTrue(Gate::forUser($this->hrgaUser)->allows('manage-knowledge-documents'));
        $this->assertFalse(Gate::forUser($this->supervisor)->allows('manage-knowledge-documents'));
        $this->assertFalse(Gate::forUser($this->employee)->allows('manage-knowledge-documents'));

        // Filament canCreate check
        $this->actingAs($this->admin);
        $this->assertTrue(KnowledgeDocumentResource::canCreate());

        $this->actingAs($this->supervisor);
        $this->assertFalse(KnowledgeDocumentResource::canCreate());

        // API store check: Employee & Supervisor dilarang create
        $payload = [
            'title' => 'SOP Keselamatan Kerja Baru',
            'division_id' => $this->divisionA->id,
            'type' => 'sop',
            'description' => 'Petunjuk K3L',
        ];

        $resEmp = $this->actingAs($this->employee)->postJson('/api/knowledge-documents', $payload);
        $resEmp->assertForbidden();

        $resSpv = $this->actingAs($this->supervisor)->postJson('/api/knowledge-documents', $payload);
        $resSpv->assertForbidden();

        // Quality & Admin diizinkan create
        $resQlt = $this->actingAs($this->quality)->postJson('/api/knowledge-documents', $payload);
        $resQlt->assertCreated();
    }

    /**
     * Poin 3: Buat post-test dari BA yang disetujui + publish materi Learning berstatus 'candidate':
     * HANYA role Quality/HRGA dan Admin via Gate 'manage-learning-materials'.
     */
    public function test_poin_3_manage_learning_materials_gate_and_candidate_pipeline_policy(): void
    {
        // Gate check
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-learning-materials'));
        $this->assertTrue(Gate::forUser($this->quality)->allows('manage-learning-materials'));
        $this->assertTrue(Gate::forUser($this->hrgaUser)->allows('manage-learning-materials'));
        $this->assertFalse(Gate::forUser($this->supervisor)->allows('manage-learning-materials'));
        $this->assertFalse(Gate::forUser($this->employee)->allows('manage-learning-materials'));

        $category = LearningCategory::create([
            'name' => 'Kategori Uji',
            'created_by' => $this->admin->id,
        ]);

        $incident = BaIncident::create([
            'id' => (string) Str::uuid(),
            'nomor_ba' => 'BA/2026/09/888',
            'division_id' => $this->divisionA->id,
            'title' => 'Insiden Mesin Packaging',
            'created_by' => $this->employee->id,
            'status' => 'approved',
        ]);

        // Materi kandidat hasil BA
        $candidateMaterial = LearningMaterial::create([
            'id' => (string) Str::uuid(),
            'learning_category_id' => $category->id,
            'title' => 'Video Penanganan: BA/2026/09/888',
            'type' => 'video',
            'source_ba_id' => $incident->id,
            'status' => 'candidate',
            'created_by' => $this->supervisor->id,
        ]);

        // Supervisor dilarang mengupdate/mempublikasi materi kandidat dari BA
        $this->assertFalse($this->supervisor->can('update', $candidateMaterial));

        // Quality dan Admin diizinkan mengelola materi kandidat dari BA
        $this->assertTrue($this->admin->can('update', $candidateMaterial));
        $this->assertTrue($this->quality->can('update', $candidateMaterial));
        $this->assertTrue($this->hrgaUser->can('update', $candidateMaterial));
    }

    /**
     * Poin 4: Set xp_reward opsional di materi Learning:
     * HANYA role Quality/HRGA dan Admin via Gate 'manage-learning-materials'.
     */
    public function test_poin_4_set_xp_reward_is_gated_for_quality_and_admin(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-learning-materials'));
        $this->assertTrue(Gate::forUser($this->quality)->allows('manage-learning-materials'));
        $this->assertFalse(Gate::forUser($this->supervisor)->allows('manage-learning-materials'));
        $this->assertFalse(Gate::forUser($this->employee)->allows('manage-learning-materials'));
    }

    /**
     * Poin 5: Adjustment XP manual (koreksi ledger poin XP secara manual):
     * HANYA role Admin via Gate 'adjust-xp-manual'.
     * WAJIB selalu insert ke point_transactions dengan source_type='admin_adjustment'
     * dan TIDAK PERNAH mengedit users.xp langsung.
     */
    public function test_poin_5_manual_xp_adjustment_strictly_admin_and_ledger_based(): void
    {
        // Gate check: HANYA Admin
        $this->assertTrue(Gate::forUser($this->admin)->allows('adjust-xp-manual'));
        $this->assertFalse(Gate::forUser($this->quality)->allows('adjust-xp-manual'));
        $this->assertFalse(Gate::forUser($this->hrgaUser)->allows('adjust-xp-manual'));
        $this->assertFalse(Gate::forUser($this->supervisor)->allows('adjust-xp-manual'));
        $this->assertFalse(Gate::forUser($this->employee)->allows('adjust-xp-manual'));

        // Filament Resource UserResource hanya bisa diakses Admin
        $this->actingAs($this->admin);
        $this->assertTrue(UserResource::canAccess());

        $this->actingAs($this->quality);
        $this->assertFalse(UserResource::canAccess());

        $this->actingAs($this->supervisor);
        $this->assertFalse(UserResource::canAccess());

        // API Endpoint non-admin ditolak
        $resDenied = $this->actingAs($this->quality)->postJson("/api/admin/users/{$this->employee->id}/adjust-xp", [
            'points' => 100,
            'description' => 'Coba koreksi poin tanpa izin',
        ]);
        $resDenied->assertForbidden();

        // 1. Admin memberikan penambahan manual +150 XP
        $this->assertSame(0, (int) $this->employee->fresh()->xp);

        $resAdd = $this->actingAs($this->admin)->postJson("/api/admin/users/{$this->employee->id}/adjust-xp", [
            'points' => 150,
            'description' => 'Bonus kontribusi inisiatif keselamatan',
        ]);
        $resAdd->assertOk();
        $this->assertSame(150, $resAdd->json('data.current_xp'));

        // Verifikasi buku besar point_transactions
        $txAdd = PointTransaction::where('user_id', $this->employee->id)
            ->where('source_type', 'admin_adjustment')
            ->first();

        $this->assertNotNull($txAdd);
        $this->assertSame('xp', $txAdd->ledger_type);
        $this->assertSame(150, $txAdd->points);
        $this->assertSame('Bonus kontribusi inisiatif keselamatan', $txAdd->description);
        $this->assertSame(150, (int) $this->employee->fresh()->xp);

        // 2. Admin melakukan pengurangan manual -50 XP (misal koreksi kesalahan input)
        $resSub = $this->actingAs($this->admin)->postJson("/api/admin/users/{$this->employee->id}/adjust-xp", [
            'points' => -50,
            'description' => 'Koreksi kelebihan alokasi poin',
        ]);
        $resSub->assertOk();
        $this->assertSame(100, $resSub->json('data.current_xp'));

        $this->assertSame(2, PointTransaction::where('user_id', $this->employee->id)->count());
        $this->assertSame(100, (int) $this->employee->fresh()->xp);
    }

    /**
     * Poin 6: Lihat rekap Poin CPS ERA semua karyawan (evaluasi kinerja HRD):
     * HANYA role Quality/HRGA dan Admin via Gate 'view-kpi-summary' dan UserKpiYearlyResource.
     */
    public function test_poin_6_view_yearly_kpi_summary_report_access_and_table(): void
    {
        // Gate check
        $this->assertTrue(Gate::forUser($this->admin)->allows('view-kpi-summary'));
        $this->assertTrue(Gate::forUser($this->quality)->allows('view-kpi-summary'));
        $this->assertTrue(Gate::forUser($this->hrgaUser)->allows('view-kpi-summary'));
        $this->assertFalse(Gate::forUser($this->supervisor)->allows('view-kpi-summary'));
        $this->assertFalse(Gate::forUser($this->employee)->allows('view-kpi-summary'));

        // Resource static check
        $this->actingAs($this->admin);
        $this->assertTrue(UserKpiYearlyResource::canAccess());
        $this->assertFalse(UserKpiYearlyResource::canCreate()); // Read-only

        $this->actingAs($this->quality);
        $this->assertTrue(UserKpiYearlyResource::canAccess());

        $this->actingAs($this->supervisor);
        $this->assertFalse(UserKpiYearlyResource::canAccess());

        // Buat data rekap KPI tahunan
        UserKpiYearly::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'period_year' => (int) now()->year,
            'materials_completed_count' => 3,
            'poin_cps_era_earned' => 2,
            'poin_from_ba' => 1,
            'poin_from_materi' => 1,
        ]);

        // HTTP Filament panel route access
        $this->actingAs($this->admin)->get('/admin/user-kpi-yearlies')->assertOk();
        $this->actingAs($this->quality)->get('/admin/user-kpi-yearlies')->assertOk();
        $this->actingAs($this->supervisor)->get('/admin/user-kpi-yearlies')->assertForbidden();
    }
}
