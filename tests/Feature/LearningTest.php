<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\User;
use App\Models\UserLearningProgress;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;

    protected User $admin;

    protected User $quality;

    protected User $supervisor;

    protected User $employee1;

    protected User $employee2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->division = Division::firstOrFail();

        $this->admin = User::factory()->create(['division_id' => $this->division->id]);
        $this->admin->assignRole('admin');

        $this->quality = User::factory()->create(['division_id' => $this->division->id]);
        $this->quality->assignRole('quality');

        $this->supervisor = User::factory()->create(['division_id' => $this->division->id]);
        $this->supervisor->assignRole('supervisor');

        $this->employee1 = User::factory()->create(['division_id' => $this->division->id]);
        $this->employee1->assignRole('employee');

        $this->employee2 = User::factory()->create(['division_id' => $this->division->id]);
        $this->employee2->assignRole('employee');
    }

    public function test_only_quality_and_admin_can_create_learning_category(): void
    {
        // 1. Employee cannot create category (403 Forbidden)
        $resEmp = $this->actingAs($this->employee1)->postJson('/api/learning-categories', [
            'name' => 'Kategori Employee',
        ]);
        $resEmp->assertForbidden();

        // 2. Supervisor cannot create category (403 Forbidden)
        $resSpv = $this->actingAs($this->supervisor)->postJson('/api/learning-categories', [
            'name' => 'Kategori Supervisor',
        ]);
        $resSpv->assertForbidden();

        // 3. Quality CAN create category (201 Created)
        $resQuality = $this->actingAs($this->quality)->postJson('/api/learning-categories', [
            'name' => 'Kategori Quality Assurance',
        ]);
        $resQuality->assertCreated();
        $this->assertDatabaseHas('learning_categories', ['name' => 'Kategori Quality Assurance']);

        // 4. Admin CAN create category (201 Created)
        $resAdmin = $this->actingAs($this->admin)->postJson('/api/learning-categories', [
            'name' => 'Kategori Standar Operasional',
        ]);
        $resAdmin->assertCreated();
        $this->assertDatabaseHas('learning_categories', ['name' => 'Kategori Standar Operasional']);
    }

    public function test_employee_creates_material_with_forced_draft_status(): void
    {
        $category = LearningCategory::create([
            'name' => 'Teknik Produksi',
            'created_by' => $this->admin->id,
        ]);

        // Employee attempts to pass status='published'
        $response = $this->actingAs($this->employee1)->postJson('/api/learning-materials', [
            'learning_category_id' => $category->id,
            'title' => 'Teknik Setting Moulding Mesin Injeksi',
            'type' => 'tutorial',
            'description' => 'Tutorial langkah setting mesin injection moulding.',
            'status' => 'published',
        ]);

        $response->assertCreated();
        $materialId = $response->json('data.id');

        // Status must be forced to 'draft'
        $this->assertSame('draft', $response->json('data.status'));
        $this->assertDatabaseHas('learning_materials', [
            'id' => $materialId,
            'status' => 'draft',
            'created_by' => $this->employee1->id,
        ]);
    }

    public function test_admin_and_quality_can_create_published_material(): void
    {
        $category = LearningCategory::create([
            'name' => 'Quality Control',
            'created_by' => $this->quality->id,
        ]);

        $response = $this->actingAs($this->quality)->postJson('/api/learning-materials', [
            'learning_category_id' => $category->id,
            'title' => 'Standar Inspeksi Produk Defect',
            'type' => 'dokumen',
            'status' => 'published',
        ]);

        $response->assertCreated();
        $this->assertSame('published', $response->json('data.status'));
        $this->assertDatabaseHas('learning_materials', [
            'title' => 'Standar Inspeksi Produk Defect',
            'status' => 'published',
        ]);
    }

    public function test_progress_tracking_is_stored_per_user(): void
    {
        $category = LearningCategory::create([
            'name' => 'Pneumatik & Hidrolik',
            'created_by' => $this->admin->id,
        ]);

        $material = LearningMaterial::create([
            'learning_category_id' => $category->id,
            'title' => 'Prinsip Kerja Kompresor',
            'type' => 'video',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        // User 1 updates progress to 60%
        $resUser1 = $this->actingAs($this->employee1)->patchJson("/api/learning-materials/{$material->id}/progress", [
            'progress_percent' => 60,
        ]);
        $resUser1->assertOk();
        $this->assertSame(60, $resUser1->json('data.progress_percent'));

        // Check progress from User 1 viewpoint
        $showUser1 = $this->actingAs($this->employee1)->getJson("/api/learning-materials/{$material->id}");
        $showUser1->assertOk();
        $this->assertSame(60, $showUser1->json('data.progress_percent'));

        // Check progress from User 2 viewpoint (must be 0%, not affected by User 1)
        $showUser2 = $this->actingAs($this->employee2)->getJson("/api/learning-materials/{$material->id}");
        $showUser2->assertOk();
        $this->assertSame(0, $showUser2->json('data.progress_percent'));
    }

    public function test_progress_tracking_no_duplicate_rows_on_upsert(): void
    {
        $category = LearningCategory::create([
            'name' => 'Elektrikal',
            'created_by' => $this->admin->id,
        ]);

        $material = LearningMaterial::create([
            'learning_category_id' => $category->id,
            'title' => 'Dasar Kelistrikan 3 Fasa',
            'type' => 'artikel',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        // Multiple updates for the same user and material
        $this->actingAs($this->employee1)->patchJson("/api/learning-materials/{$material->id}/progress", ['progress_percent' => 25]);
        $this->actingAs($this->employee1)->patchJson("/api/learning-materials/{$material->id}/progress", ['progress_percent' => 50]);
        $this->actingAs($this->employee1)->patchJson("/api/learning-materials/{$material->id}/progress", ['progress_percent' => 75]);

        // Assert only exactly 1 record exists in database
        $this->assertSame(
            1,
            UserLearningProgress::where('user_id', $this->employee1->id)
                ->where('learning_material_id', $material->id)
                ->count()
        );

        $latestProgress = UserLearningProgress::where('user_id', $this->employee1->id)
            ->where('learning_material_id', $material->id)
            ->first();

        $this->assertSame(75, $latestProgress->progress_percent);
        $this->assertNull($latestProgress->completed_at);
    }

    public function test_progress_100_percent_sets_completed_at(): void
    {
        $category = LearningCategory::create([
            'name' => 'Safety K3',
            'created_by' => $this->admin->id,
        ]);

        $material = LearningMaterial::create([
            'learning_category_id' => $category->id,
            'title' => 'Evakuasi Tanggap Darurat Kebakaran',
            'type' => 'video',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        // User reaches 100%
        $response = $this->actingAs($this->employee1)->patchJson("/api/learning-materials/{$material->id}/progress", [
            'progress_percent' => 100,
        ]);

        $response->assertOk();
        $this->assertNotNull($response->json('data.completed_at'));

        $record = UserLearningProgress::where('user_id', $this->employee1->id)
            ->where('learning_material_id', $material->id)
            ->first();

        $this->assertNotNull($record->completed_at);
    }

    public function test_get_my_learning_progress_endpoint(): void
    {
        $category = LearningCategory::create([
            'name' => 'Manufaktur',
            'created_by' => $this->admin->id,
        ]);

        $mat1 = LearningMaterial::create([
            'learning_category_id' => $category->id,
            'title' => 'Materi 1',
            'type' => 'dokumen',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        $mat2 = LearningMaterial::create([
            'learning_category_id' => $category->id,
            'title' => 'Materi 2',
            'type' => 'video',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        // Mat 1 -> 100%, Mat 2 -> 50%
        $this->actingAs($this->employee1)->patchJson("/api/learning-materials/{$mat1->id}/progress", ['progress_percent' => 100]);
        $this->actingAs($this->employee1)->patchJson("/api/learning-materials/{$mat2->id}/progress", ['progress_percent' => 50]);

        $response = $this->actingAs($this->employee1)->getJson('/api/me/learning-progress');
        $response->assertOk();

        $this->assertSame(2, $response->json('data.summary.total_materials_started'));
        $this->assertSame(1, $response->json('data.summary.total_materials_completed'));
        $this->assertEquals(75.0, $response->json('data.summary.average_progress_percent'));
    }

    public function test_get_learning_categories_and_category_materials_with_user_progress(): void
    {
        $cat1 = LearningCategory::create(['name' => 'Kategori A', 'created_by' => $this->admin->id]);
        $cat2 = LearningCategory::create(['name' => 'Kategori B', 'created_by' => $this->admin->id]);

        $mat1 = LearningMaterial::create([
            'learning_category_id' => $cat1->id,
            'title' => 'Materi A1',
            'type' => 'video',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        // User sets 80% on mat1
        $this->actingAs($this->employee1)->patchJson("/api/learning-materials/{$mat1->id}/progress", ['progress_percent' => 80]);

        // 1. List categories
        $listCatRes = $this->actingAs($this->employee1)->getJson('/api/learning-categories');
        $listCatRes->assertOk();
        $this->assertCount(2, $listCatRes->json('data'));

        // 2. List materials under category A
        $listMatRes = $this->actingAs($this->employee1)->getJson("/api/learning-categories/{$cat1->id}/materials");
        $listMatRes->assertOk();
        $this->assertCount(1, $listMatRes->json('data.materials'));
        $this->assertSame(80, $listMatRes->json('data.materials.0.progress_percent'));
    }
}
