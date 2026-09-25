<?php

namespace Tests\Feature\Security;

use App\Models\Division;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserLearningProgress;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit pre-launch: materi Learning (dan post-test-nya) yang belum terbit hanya boleh dibuka
 * user yang lolos LearningMaterialPolicy::update; selain itu 404.
 */
class UnpublishedLearningMaterialAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private LearningCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->employee = User::factory()->create(['division_id' => Division::where('name', 'Produksi')->value('id')]);
        $this->employee->assignRole('employee');
        $this->category = LearningCategory::create(['name' => 'Kategori Uji', 'created_by' => $this->employee->id]);
    }

    private function material(string $status, ?User $creator = null): LearningMaterial
    {
        $material = LearningMaterial::create([
            'learning_category_id' => $this->category->id,
            'title' => "Materi {$status}",
            'type' => 'artikel',
            'description' => 'Ringkasan hasil laporan CAPA yang belum diterbitkan.',
            'status' => $status,
            'created_by' => ($creator ?? $this->employee)->id,
        ]);

        Quiz::create([
            'title' => "Post-Test {$status}",
            'type' => 'post_test',
            'related_type' => 'learning_material',
            'related_id' => $material->id,
            'points_reward' => 10,
        ]);

        // Progress 100% supaya gating post-test tidak menutupi pemeriksaan status.
        UserLearningProgress::create([
            'user_id' => $this->employee->id,
            'learning_material_id' => $material->id,
            'progress_percent' => 100,
        ]);

        return $material;
    }

    public function test_employee_gets_404_on_a_candidate_material_and_its_post_test(): void
    {
        $quality = User::factory()->create();
        $quality->assignRole('quality');
        $candidate = $this->material('candidate', $quality);

        $this->actingAs($this->employee)->get(route('learning.show', $candidate))->assertNotFound();
        $this->actingAs($this->employee)->get(route('learning.post-test', $candidate))->assertNotFound();
    }

    public function test_employee_can_open_a_published_material_and_its_post_test(): void
    {
        $published = $this->material('published');

        $this->actingAs($this->employee)->get(route('learning.show', $published))->assertOk()->assertSee($published->title);
        $this->actingAs($this->employee)->get(route('learning.post-test', $published))->assertOk();
    }

    public function test_users_allowed_to_update_can_still_preview_unpublished_materials(): void
    {
        $quality = User::factory()->create();
        $quality->assignRole('quality');
        $candidate = $this->material('candidate', $quality);
        $ownDraft = $this->material('draft');

        $this->actingAs($quality)->get(route('learning.show', $candidate))->assertOk();
        $this->actingAs($this->employee)->get(route('learning.show', $ownDraft))->assertOk();
    }
}
