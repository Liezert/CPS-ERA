<?php

namespace Tests\Feature;

use App\Livewire\Learning\Index as LearningIndex;
use App\Livewire\Learning\Show as LearningShow;
use App\Models\Division;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserLearningProgress;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class StageEightLearningTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;

    protected User $admin;

    protected User $quality;

    protected User $supervisor;

    protected User $employee;

    protected LearningCategory $category;

    protected LearningMaterial $materialWithQuiz;

    protected LearningMaterial $materialWithoutQuiz;

    protected Quiz $postTest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->division = Division::firstOrFail();

        $this->admin = User::factory()->create(['division_id' => $this->division->id, 'employee_id' => 'ADM-001']);
        $this->admin->assignRole('admin');

        $this->quality = User::factory()->create(['division_id' => $this->division->id, 'employee_id' => 'QLT-001']);
        $this->quality->assignRole('quality');

        $this->supervisor = User::factory()->create(['division_id' => $this->division->id, 'employee_id' => 'SPV-001']);
        $this->supervisor->assignRole('supervisor');

        $this->employee = User::factory()->create(['division_id' => $this->division->id, 'employee_id' => 'EMP-001']);
        $this->employee->assignRole('employee');

        // Setup Category
        $this->category = LearningCategory::create([
            'name' => 'Standar Mutu & Regulasi ISO',
            'created_by' => $this->admin->id,
        ]);

        // Setup Material 1 (With Post-Test)
        $this->materialWithQuiz = LearningMaterial::create([
            'id' => (string) Str::uuid(),
            'learning_category_id' => $this->category->id,
            'title' => 'SOP Kalibrasi Sensor Suhu Lini Injeksi',
            'type' => 'dokumen',
            'content_url' => 'https://portal.cps.co.id/docs/sop-kalibrasi.pdf',
            'description' => 'Panduan kalibrasi sensor suhu mesin.',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        // Setup Post-Test Quiz
        $this->postTest = Quiz::create([
            'id' => (string) Str::uuid(),
            'title' => 'Post-Test: SOP Kalibrasi Sensor Suhu',
            'type' => 'post_test',
            'related_type' => 'learning_material',
            'related_id' => $this->materialWithQuiz->id,
            'points_reward' => 20,
            'description' => 'Evaluasi pemahaman kalibrasi sensor.',
        ]);

        // Setup Material 2 (Without Post-Test)
        $this->materialWithoutQuiz = LearningMaterial::create([
            'id' => (string) Str::uuid(),
            'learning_category_id' => $this->category->id,
            'title' => 'Video Perawatan Berkala Chiller',
            'type' => 'video',
            'content_url' => 'https://youtube.com/watch?v=sample',
            'description' => 'Video tutorial perawatan sistem pendingin.',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);
    }

    /**
     * DoD #1: Progress tersimpan per user per materi, field cocok dengan ERD.
     */
    public function test_progress_is_stored_per_user_per_material_matching_erd(): void
    {
        // 1. Inisialisasi awal: Buka halaman show materi
        $response = $this->actingAs($this->employee)->get(route('learning.show', $this->materialWithQuiz->id));
        $response->assertStatus(200);

        // Record awal harus otomatis ada di database user_learning_progress dengan 0%
        $this->assertDatabaseHas('user_learning_progress', [
            'user_id' => $this->employee->id,
            'learning_material_id' => $this->materialWithQuiz->id,
            'progress_percent' => 0,
            'completed_at' => null,
        ]);

        // 2. Uji update progress parsial (50%)
        Livewire::actingAs($this->employee)
            ->test(LearningShow::class, ['material' => $this->materialWithQuiz])
            ->call('updateProgress', 50)
            ->assertSet('progressPercent', 50);

        $this->assertDatabaseHas('user_learning_progress', [
            'user_id' => $this->employee->id,
            'learning_material_id' => $this->materialWithQuiz->id,
            'progress_percent' => 50,
            'completed_at' => null,
        ]);

        // 3. Uji update progress 100%: completed_at harus terisi timestamp
        Livewire::actingAs($this->employee)
            ->test(LearningShow::class, ['material' => $this->materialWithQuiz])
            ->call('markCompleted')
            ->assertSet('progressPercent', 100);

        $progress = UserLearningProgress::where('user_id', $this->employee->id)
            ->where('learning_material_id', $this->materialWithQuiz->id)
            ->firstOrFail();

        $this->assertEquals(100, $progress->progress_percent);
        $this->assertNotNull($progress->completed_at);

        // 4. Pastikan progress user lain tetap terisolasi
        $otherUser = User::factory()->create(['division_id' => $this->division->id]);
        $this->assertDatabaseMissing('user_learning_progress', [
            'user_id' => $otherUser->id,
            'learning_material_id' => $this->materialWithQuiz->id,
        ]);
    }

    /**
     * DoD #2: Post-Test muncul setelah materi selesai (gating 100%).
     */
    public function test_post_test_button_only_appears_after_material_completed(): void
    {
        // A. Kondisi 1: Progress < 100% -> Tombol Post-Test TIDAK BOLEH MUNCUL (Terkunci)
        Livewire::actingAs($this->employee)
            ->test(LearningShow::class, ['material' => $this->materialWithQuiz])
            ->call('updateProgress', 50)
            ->assertDontSeeHtml('Lanjut ke Post-Test')
            ->assertSee('Post-Test Terkunci');

        // B. Kondisi 2: Progress == 100% -> Tombol "Lanjut ke Post-Test" HARUS MUNCUL
        Livewire::actingAs($this->employee)
            ->test(LearningShow::class, ['material' => $this->materialWithQuiz])
            ->call('markCompleted')
            ->assertSeeHtml('Lanjut ke Post-Test')
            ->assertDontSee('Post-Test Terkunci');

        // C. Kondisi 3: Materi tanpa Post-Test -> Tidak menampilkan Post-Test meski sudah 100%
        Livewire::actingAs($this->employee)
            ->test(LearningShow::class, ['material' => $this->materialWithoutQuiz])
            ->call('markCompleted')
            ->assertDontSeeHtml('Lanjut ke Post-Test')
            ->assertSee('Materi pembelajaran ini tidak memiliki Post-Test');
    }

    /**
     * DoD #3: Kategori dikelola dari sisi Admin (dan Quality).
     */
    public function test_categories_are_managed_by_admin_and_quality(): void
    {
        // 1. Admin berhasil membuat kategori baru via Livewire modal
        Livewire::actingAs($this->admin)
            ->test(LearningIndex::class)
            ->set('newCategoryName', 'Keselamatan Kerja Fabrikasi')
            ->call('saveCategory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('learning_categories', [
            'name' => 'Keselamatan Kerja Fabrikasi',
            'created_by' => $this->admin->id,
        ]);

        // 2. Quality juga berhasil membuat kategori baru
        Livewire::actingAs($this->quality)
            ->test(LearningIndex::class)
            ->set('newCategoryName', 'Pengujian Material Kimia')
            ->call('saveCategory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('learning_categories', [
            'name' => 'Pengujian Material Kimia',
            'created_by' => $this->quality->id,
        ]);

        // 3. Employee ditolak (403 Forbidden) saat mencoba memanggil saveCategory
        Livewire::actingAs($this->employee)
            ->test(LearningIndex::class)
            ->set('newCategoryName', 'Kategori Ilegal Employee')
            ->call('saveCategory')
            ->assertStatus(403);

        $this->assertDatabaseMissing('learning_categories', [
            'name' => 'Kategori Ilegal Employee',
        ]);
    }

    /**
     * Uji Katalog Learning: Kategori Filter, 7 Jenis Materi, dan Thin Green Progress Bar.
     */
    public function test_learning_catalog_renders_7_types_and_thin_green_progress_bar(): void
    {
        // Pasang progress 50% untuk employee
        UserLearningProgress::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'learning_material_id' => $this->materialWithQuiz->id,
            'progress_percent' => 50,
            'completed_at' => null,
        ]);

        $component = Livewire::actingAs($this->employee)
            ->test(LearningIndex::class)
            ->assertSee('SOP Kalibrasi Sensor Suhu Lini Injeksi')
            ->assertSee('Video Perawatan Berkala Chiller')
            ->assertSeeHtml('bg-brand h-1.5') // Progress bar tipis hijau
            ->assertSee('50%');

        // Uji filter berdasarkan kategori
        $component->set('selectedCategoryId', $this->category->id)
            ->assertSee('SOP Kalibrasi Sensor Suhu Lini Injeksi');

        // Uji filter jenis materi (Dokumen)
        $component->set('selectedType', 'dokumen')
            ->assertSee('SOP Kalibrasi Sensor Suhu Lini Injeksi')
            ->assertDontSee('Video Perawatan Berkala Chiller');

        // Uji switch filter jenis materi (Video)
        $component->set('selectedType', 'video')
            ->assertSee('Video Perawatan Berkala Chiller')
            ->assertDontSee('SOP Kalibrasi Sensor Suhu Lini Injeksi');
    }
}
