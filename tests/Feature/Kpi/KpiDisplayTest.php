<?php

namespace Tests\Feature\Kpi;

use App\Filament\Resources\UserKpiYearlies\Pages\ListUserKpiYearlies;
use App\Livewire\Dashboard;
use App\Livewire\Learning\PostTest;
use App\Livewire\Learning\Show as LearningShow;
use App\Livewire\Profile\Index as ProfileIndex;
use App\Models\Division;
use App\Models\KpiSetting;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserKpiYearly;
use App\Models\UserLearningProgress;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tampilan KPI (aturan owner): Dashboard, Profil, dan rekap admin memakai satu sumber (kalculator
 * berbasis attempt), bukan counter lama; hasil post-test hanya persentase + status lulus.
 */
class KpiDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private LearningCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->employee = $this->userWithRole('employee');
        $this->category = LearningCategory::create(['name' => 'Kategori KPI', 'created_by' => $this->employee->id]);

        KpiSetting::create([
            'period_start' => now('Asia/Jakarta')->startOfYear()->toDateString(),
            'period_end' => now('Asia/Jakarta')->endOfYear()->toDateString(),
            'target_materials' => 5,
        ]);

        // Counter lama sengaja diisi 4: tampilan tidak boleh memakai angka ini.
        UserKpiYearly::create([
            'user_id' => $this->employee->id,
            'period_year' => (int) now()->year,
            'materials_completed_count' => 4,
            'poin_cps_era_earned' => 0,
            'poin_from_ba' => 0,
            'poin_from_materi' => 0,
        ]);

        foreach (['A', 'B'] as $title) {
            QuizAttempt::create([
                'quiz_id' => $this->material($title)->postTest()->firstOrFail()->id,
                'user_id' => $this->employee->id,
                'score' => 100,
                'passed' => true,
                'points_earned' => 0,
                'attempted_at' => now(),
            ]);
        }
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['division_id' => Division::where('name', 'Produksi')->value('id')]);
        $user->assignRole($role);

        return $user;
    }

    private function material(string $title, int $pointsReward = 0): LearningMaterial
    {
        $material = LearningMaterial::create([
            'learning_category_id' => $this->category->id,
            'title' => "Materi {$title}",
            'type' => 'artikel',
            'status' => 'published',
            'created_by' => $this->employee->id,
        ]);

        Quiz::create([
            'title' => "Post-Test {$title}",
            'type' => 'post_test',
            'related_type' => 'learning_material',
            'related_id' => $material->id,
            'points_reward' => $pointsReward,
        ]);

        return $material;
    }

    public function test_dashboard_shows_calculated_progress_percentage_and_period_not_the_legacy_counter(): void
    {
        Livewire::actingAs($this->employee)->test(Dashboard::class)
            ->assertSee('2 dari 5 materi')
            ->assertSee('40%')
            ->assertSee('Post-test 100%')
            ->assertSee(now('Asia/Jakarta')->startOfYear()->translatedFormat('d M Y'))
            ->assertDontSee('4 dari 5 materi')
            ->assertDontSee('Jalur B');
    }

    public function test_profile_uses_the_same_source_as_the_dashboard(): void
    {
        Livewire::actingAs($this->employee)->test(ProfileIndex::class)
            ->assertSee('2 dari 5 materi')
            ->assertSee('40%')
            ->assertSee(now('Asia/Jakarta')->startOfYear()->translatedFormat('d M Y'))
            ->assertDontSee('4 dari 5 materi');
    }

    public function test_reaching_the_target_is_shown_as_complete(): void
    {
        foreach (['C', 'D', 'E'] as $title) {
            QuizAttempt::create([
                'quiz_id' => $this->material($title)->postTest()->firstOrFail()->id,
                'user_id' => $this->employee->id,
                'score' => 100,
                'passed' => true,
                'points_earned' => 0,
                'attempted_at' => now(),
            ]);
        }

        Livewire::actingAs($this->employee)->test(Dashboard::class)
            ->assertSee('5 dari 5 materi')
            ->assertSee('100%')
            ->assertSee('Target periode tercapai');
    }

    public function test_admin_recap_shows_calculated_progress_instead_of_the_bundle_counter(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $record = UserKpiYearly::where('user_id', $this->employee->id)->firstOrFail();

        Livewire::actingAs($this->userWithRole('admin'))->test(ListUserKpiYearlies::class)
            ->assertTableColumnStateSet('kpi_progress', '2 dari 5 materi (40%)', $record)
            ->assertTableColumnDoesNotExist('materials_completed_count');
    }

    public function test_material_page_does_not_promise_kpi_points_per_material(): void
    {
        $material = $this->material('Janji', pointsReward: 20);
        UserLearningProgress::create(['user_id' => $this->employee->id, 'learning_material_id' => $material->id, 'progress_percent' => 100, 'completed_at' => now()]);

        Livewire::actingAs($this->employee)->test(LearningShow::class, ['material' => $material])
            ->assertDontSee('Poin KPI jika lulus')
            ->assertSee('Lulus dengan skor 100% menambah progres KPI periode ini');
    }

    public function test_post_test_result_shows_only_percentage_and_status_without_answer_keys(): void
    {
        $material = $this->material('Hasil');
        $quiz = $material->postTest()->firstOrFail();
        $answers = [];

        foreach ([1, 2] as $i) {
            $question = QuizQuestion::create(['quiz_id' => $quiz->id, 'question_text' => "Soal {$i}", 'order_index' => $i, 'allow_multiple_answers' => false]);
            $right = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => "Opsi {$i}A", 'is_correct' => true]);
            $wrong = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => "Opsi {$i}B", 'is_correct' => false]);
            $answers[$question->id] = $i === 1 ? $right->id : $wrong->id;
        }
        UserLearningProgress::create(['user_id' => $this->employee->id, 'learning_material_id' => $material->id, 'progress_percent' => 100, 'completed_at' => now()]);

        $component = Livewire::actingAs($this->employee)->test(PostTest::class, ['material' => $material])
            ->set('userAnswers', $answers)
            ->call('submitPostTest')
            ->assertSet('score', 50)
            ->assertSet('passed', false)
            ->assertSee('50%')
            ->assertSee('Belum Memenuhi Syarat Kelulusan KPI (Wajib 100%)');

        $html = $component->html();
        $state = json_encode($component->snapshot);

        foreach ([$html, $state] as $output) {
            $this->assertStringNotContainsString('is_correct', $output);
            $this->assertDoesNotMatchRegularExpression('/jawaban (anda )?(benar|salah)|correct_option|seluruh soal dengan benar/i', $output);
        }
    }
}
