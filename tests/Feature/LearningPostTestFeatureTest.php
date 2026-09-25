<?php

namespace Tests\Feature;

use App\Filament\Resources\LearningMaterials\Pages\CreateLearningMaterial;
use App\Filament\Resources\LearningMaterials\Pages\EditLearningMaterial;
use App\Filament\Widgets\AdminOverviewWidget;
use App\Livewire\Learning\PostTest;
use App\Livewire\Learning\Show as LearningShow;
use App\Models\Division;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserLearningProgress;
use App\Services\KpiContributionCalculator;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LearningPostTestFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $employee;

    protected User $admin;

    protected LearningCategory $category;

    protected LearningMaterial $material;

    protected Quiz $postTest;

    protected QuizQuestion $question1;

    protected QuizQuestion $question2;

    protected QuizOption $opt1Correct;

    protected QuizOption $opt1Wrong;

    protected QuizOption $opt2Correct;

    protected QuizOption $opt2Wrong;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $division = Division::firstOrFail();

        $this->admin = User::factory()->create(['division_id' => $division->id, 'employee_id' => 'CPS-00001']);
        $this->admin->assignRole('admin');

        $this->employee = User::factory()->create(['division_id' => $division->id, 'employee_id' => 'CPS-00002']);
        $this->employee->assignRole('employee');

        $this->category = LearningCategory::create([
            'name' => 'Standar Mutu & Regulasi ISO',
            'created_by' => $this->admin->id,
        ]);

        $this->material = LearningMaterial::create([
            'id' => (string) Str::uuid(),
            'learning_category_id' => $this->category->id,
            'title' => 'SOP Kalibrasi Instrumen Pengukuran Suhu',
            'type' => 'dokumen',
            'content_url' => 'https://portal.cps.co.id/docs/sop.pdf',
            'description' => 'Materi pelatihan kalibrasi sensor.',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        $this->postTest = Quiz::create([
            'id' => (string) Str::uuid(),
            'title' => 'Post-Test: Pemahaman SOP Kalibrasi',
            'type' => 'post_test',
            'related_type' => 'learning_material',
            'related_id' => $this->material->id,
            'points_reward' => 20,
            'description' => 'Uji pemahaman prosedur kalibrasi standar ISO.',
        ]);

        $this->question1 = QuizQuestion::create([
            'id' => (string) Str::uuid(),
            'quiz_id' => $this->postTest->id,
            'question_text' => 'Berapa toleransi suhu maksimal yang diizinkan?',
            'order_index' => 1,
        ]);

        $this->opt1Correct = QuizOption::create([
            'id' => (string) Str::uuid(),
            'quiz_question_id' => $this->question1->id,
            'option_text' => '±0.5°C',
            'is_correct' => true,
        ]);

        $this->opt1Wrong = QuizOption::create([
            'id' => (string) Str::uuid(),
            'quiz_question_id' => $this->question1->id,
            'option_text' => '±5.0°C',
            'is_correct' => false,
        ]);

        $this->question2 = QuizQuestion::create([
            'id' => (string) Str::uuid(),
            'quiz_id' => $this->postTest->id,
            'question_text' => 'Berapa interval waktu verifikasi berkala sensor?',
            'order_index' => 2,
        ]);

        $this->opt2Correct = QuizOption::create([
            'id' => (string) Str::uuid(),
            'quiz_question_id' => $this->question2->id,
            'option_text' => 'Setiap 1 bulan sekali',
            'is_correct' => true,
        ]);

        $this->opt2Wrong = QuizOption::create([
            'id' => (string) Str::uuid(),
            'quiz_question_id' => $this->question2->id,
            'option_text' => 'Setiap 5 tahun sekali',
            'is_correct' => false,
        ]);
    }

    /**
     * Test 1: Pengguna dengan progress < 100% dilarang membuka halaman Post-Test (redirect dengan pesan).
     */
    public function test_post_test_is_gated_and_redirects_if_progress_below_100(): void
    {
        // Set progress baru 50%
        UserLearningProgress::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'learning_material_id' => $this->material->id,
            'progress_percent' => 50,
        ]);

        Livewire::actingAs($this->employee)
            ->test(PostTest::class, ['material' => $this->material])
            ->assertRedirect(route('learning.show', $this->material));
    }

    /**
     * Test 2: Pengguna dengan progress 100% dapat mengakses dan melihat halaman Post-Test.
     */
    public function test_post_test_accessible_when_material_completed_100(): void
    {
        UserLearningProgress::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'learning_material_id' => $this->material->id,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($this->employee)
            ->test(PostTest::class, ['material' => $this->material])
            ->assertOk()
            ->assertSee('Post-Test: Pemahaman SOP Kalibrasi')
            ->assertSee('Berapa toleransi suhu maksimal yang diizinkan?')
            ->assertSee('±0.5°C')
            ->assertSee('Standar Kelulusan KPI:');
    }

    /**
     * Test 3: Tombol di halaman learning.show menautkan langsung ke learning.post-test tanpa arrow icon.
     */
    public function test_learning_show_links_to_post_test_without_arrow(): void
    {
        UserLearningProgress::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'learning_material_id' => $this->material->id,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($this->employee)
            ->test(LearningShow::class, ['material' => $this->material])
            ->assertSeeHtml(route('learning.post-test', $this->material))
            ->assertDontSee('&rarr;');
    }

    /**
     * Test 4: Menyelesaikan evaluasi Post-Test dengan skor 100% mencatatkan lulus dan kredit KPI.
     */
    public function test_submitting_post_test_with_passing_score_awards_points(): void
    {
        UserLearningProgress::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'learning_material_id' => $this->material->id,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($this->employee)
            ->test(PostTest::class, ['material' => $this->material])
            ->call('selectOption', $this->question1->id, $this->opt1Correct->id)
            ->call('selectOption', $this->question2->id, $this->opt2Correct->id)
            ->call('submitPostTest')
            ->assertSet('score', 100)
            ->assertSet('passed', true)
            ->assertSee('Evaluasi Berhasil Diselesaikan (Lulus 100%)');

        // Verifikasi tabel quiz_attempts
        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $this->postTest->id,
            'user_id' => $this->employee->id,
            'score' => 100,
            'passed' => true,
        ]);

        // Verifikasi kredit KPI: progres periode dihitung dari attempt
        $this->assertSame(1, $this->kpiCompleted());
    }

    private function kpiCompleted(): int
    {
        return app(KpiContributionCalculator::class)->calculate($this->employee)['completed'];
    }

    /**
     * Test 5: Menyelesaikan evaluasi Post-Test dengan skor < 100% mencatatkan tidak lulus dan tidak dapat kredit KPI.
     */
    public function test_submitting_post_test_failing_score_awards_no_points(): void
    {
        UserLearningProgress::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'learning_material_id' => $this->material->id,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($this->employee)
            ->test(PostTest::class, ['material' => $this->material])
            ->call('selectOption', $this->question1->id, $this->opt1Wrong->id)
            ->call('selectOption', $this->question2->id, $this->opt2Wrong->id)
            ->call('submitPostTest')
            ->assertSet('score', 0)
            ->assertSet('passed', false)
            ->assertSee('Belum Memenuhi Syarat Kelulusan KPI (Wajib 100%)');

        // Verifikasi tabel quiz_attempts
        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $this->postTest->id,
            'user_id' => $this->employee->id,
            'score' => 0,
            'passed' => false,
        ]);

        // Verifikasi tidak ada penambahan progres KPI
        $this->assertSame(0, $this->kpiCompleted());
    }

    /**
     * Test 6: Mengulang post-test yang sudah pernah lulus tidak memberikan kredit KPI ganda di tahun yang sama.
     */
    public function test_retaking_post_test_does_not_duplicate_point_reward(): void
    {
        UserLearningProgress::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'learning_material_id' => $this->material->id,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);

        // Percobaan pertama (Lulus & dapat kredit KPI)
        Livewire::actingAs($this->employee)
            ->test(PostTest::class, ['material' => $this->material])
            ->call('selectOption', $this->question1->id, $this->opt1Correct->id)
            ->call('selectOption', $this->question2->id, $this->opt2Correct->id)
            ->call('submitPostTest')
            ->assertSet('score', 100)
            ->assertSet('passed', true);

        $this->assertSame(1, $this->kpiCompleted());

        // Percobaan kedua (Lulus lagi)
        Livewire::actingAs($this->employee)
            ->test(PostTest::class, ['material' => $this->material])
            ->call('selectOption', $this->question1->id, $this->opt1Correct->id)
            ->call('selectOption', $this->question2->id, $this->opt2Correct->id)
            ->call('submitPostTest')
            ->assertSet('score', 100)
            ->assertSet('passed', true);

        // Progres tetap 1 (materi yang sama dihitung sekali), tidak menjadi 2
        $this->assertSame(1, $this->kpiCompleted());
    }

    /**
     * Test 7: Admin dapat membuat materi baru yang otomatis menyertakan Post-Test & pertanyaannya.
     */
    public function test_admin_can_create_learning_material_with_post_test(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateLearningMaterial::class)
            ->fillForm([
                'learning_category_id' => $this->category->id,
                'title' => 'Modul QC Kalibrasi Baru',
                'type' => 'dokumen',
                'status' => 'published',
                'has_post_test' => true,
                'post_test_title' => 'Post-Test: QC Kalibrasi Baru',
                'post_test_points' => 25,
                'post_test_description' => 'Evaluasi kelayakan kalibrasi.',
                'post_test_questions' => [
                    [
                        'question_text' => 'Berapa batas margin error?',
                        'order_index' => 1,
                        'options' => [
                            ['option_text' => '0.1%', 'is_correct' => true],
                            ['option_text' => '10%', 'is_correct' => false],
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $createdMaterial = LearningMaterial::where('title', 'Modul QC Kalibrasi Baru')->firstOrFail();

        $this->assertDatabaseHas('quizzes', [
            'title' => 'Post-Test: QC Kalibrasi Baru',
            'type' => 'post_test',
            'related_type' => 'learning_material',
            'related_id' => $createdMaterial->id,
            'points_reward' => 25,
        ]);

        $quiz = $createdMaterial->postTest;
        $this->assertNotNull($quiz);
        $this->assertSame(1, $quiz->questions()->count());

        $question = $quiz->questions()->first();
        $this->assertSame('Berapa batas margin error?', $question->question_text);
        $this->assertSame(2, $question->options()->count());
        $this->assertSame(1, $question->options()->where('is_correct', true)->count());
    }

    /**
     * Test 8: Admin dapat mengedit materi dan memperbarui Post-Test.
     */
    public function test_admin_can_edit_learning_material_and_update_post_test(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EditLearningMaterial::class, [
                'record' => $this->material->id,
            ])
            ->assertSet('data.has_post_test', true)
            ->assertSet('data.post_test_points', 20)
            ->fillForm([
                'post_test_points' => 35,
                'post_test_questions' => [
                    [
                        'question_text' => 'Pertanyaan baru setelah revisi?',
                        'order_index' => 1,
                        'options' => [
                            ['option_text' => 'Revisi Benar', 'is_correct' => true],
                            ['option_text' => 'Revisi Salah', 'is_correct' => false],
                        ],
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->postTest->refresh();
        $this->assertSame(35, $this->postTest->points_reward);
        $this->assertSame(1, $this->postTest->questions()->count());
        $this->assertSame('Pertanyaan baru setelah revisi?', $this->postTest->questions()->first()->question_text);
    }

    /**
     * Test 9: Admin Panel Dashboard memuat branding CPS ERA dan navigasi resource.
     */
    public function test_admin_dashboard_renders_with_brand(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('CPS ERA Admin');
        $response->assertSee('Knowledge Repository');
        $response->assertSee('Laporan CAPA');
    }

    /**
     * Test 10: Admin Overview Widget memuat seluruh ringkasan metrik statistik operasional.
     */
    public function test_admin_overview_widget_renders_stats(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminOverviewWidget::class)
            ->assertSee('Materi Pembelajaran')
            ->assertSee('Quiz & Post-Test')
            ->assertSee('Knowledge Repository')
            ->assertSee('Laporan CAPA');
    }
}
