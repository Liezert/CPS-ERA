<?php

namespace Tests\Feature\Kpi;

use App\Filament\Resources\KpiSettings\Pages\CreateKpiSetting;
use App\Livewire\Learning\PostTest;
use App\Models\Division;
use App\Models\KpiSetting;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserLearningProgress;
use App\Services\KpiContributionCalculator;
use Carbon\Carbon;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * KPI Contribution (aturan owner): progres = jumlah materi Learning BERBEDA yang punya minimal satu
 * attempt post-test skor tepat 100 di dalam periode KPI Settings (batas hari dalam zona Asia/Jakarta).
 */
class MaterialKpiProgressTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private LearningCategory $category;

    private KpiContributionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->employee = $this->userWithRole('employee');
        $this->category = LearningCategory::create(['name' => 'Kategori KPI', 'created_by' => $this->employee->id]);
        $this->calculator = app(KpiContributionCalculator::class);

        KpiSetting::create(['period_start' => '2026-10-01', 'period_end' => '2026-10-31', 'target_materials' => 5]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['division_id' => Division::where('name', 'Produksi')->value('id')]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Materi published beserta post-test-nya.
     */
    private function material(string $title = 'Materi'): LearningMaterial
    {
        $material = LearningMaterial::create([
            'learning_category_id' => $this->category->id,
            'title' => $title,
            'type' => 'artikel',
            'status' => 'published',
            'created_by' => $this->employee->id,
        ]);

        Quiz::create([
            'title' => "Post-Test {$title}",
            'type' => 'post_test',
            'related_type' => 'learning_material',
            'related_id' => $material->id,
            'points_reward' => 0,
        ]);

        return $material;
    }

    /**
     * Attempt post-test pada waktu WIB tertentu (disimpan dalam UTC, sesuai app.timezone).
     */
    private function attempt(LearningMaterial $material, int $score, string $wib, ?User $user = null): QuizAttempt
    {
        return QuizAttempt::create([
            'quiz_id' => $material->postTest()->firstOrFail()->id,
            'user_id' => ($user ?? $this->employee)->id,
            'score' => $score,
            'passed' => $score === 100,
            'points_earned' => 0,
            'attempted_at' => Carbon::parse($wib, 'Asia/Jakarta')->utc(),
        ]);
    }

    private function progress(?User $user = null): array
    {
        return $this->calculator->calculate($user ?? $this->employee);
    }

    public function test_no_attempts_means_zero_progress(): void
    {
        $result = $this->progress();

        $this->assertSame(0, $result['completed']);
        $this->assertSame(5, $result['target']);
        $this->assertSame(0, $result['percentage']);
        $this->assertFalse($result['is_complete']);
        $this->assertSame('0 dari 5 materi', $result['summary']);
    }

    public function test_one_material_passed_with_100_counts_as_one_of_five(): void
    {
        $this->attempt($this->material(), 100, '2026-10-10 09:00');

        $result = $this->progress();
        $this->assertSame(1, $result['completed']);
        $this->assertSame(20, $result['percentage']);
        $this->assertSame('1 dari 5 materi', $result['summary']);
    }

    public function test_repeating_the_same_material_still_counts_once(): void
    {
        $material = $this->material();
        $this->attempt($material, 100, '2026-10-10 09:00');
        $this->attempt($material, 100, '2026-10-11 09:00');
        $this->attempt($material, 100, '2026-10-12 09:00');

        $this->assertSame(1, $this->progress()['completed']);
    }

    public function test_scores_below_100_are_not_counted(): void
    {
        $this->attempt($this->material('A'), 80, '2026-10-10 09:00');
        $this->attempt($this->material('B'), 99, '2026-10-10 09:00');

        $this->assertSame(0, $this->progress()['completed']);
    }

    public function test_attempts_outside_the_period_are_not_counted(): void
    {
        $this->attempt($this->material('Sebelum'), 100, '2026-09-30 23:59');
        $this->attempt($this->material('Sesudah'), 100, '2026-11-01 00:00');

        $this->assertSame(0, $this->progress()['completed']);
    }

    public function test_period_boundaries_follow_asia_jakarta_days(): void
    {
        // 23:30 WIB di hari terakhir (= 16:30 UTC) masih dihitung.
        $this->attempt($this->material('Akhir'), 100, '2026-10-31 23:30');
        // 00:30 WIB di hari pertama (= 2026-09-30 17:30 UTC) dihitung.
        $this->attempt($this->material('Awal'), 100, '2026-10-01 00:30');
        // 00:30 WIB sehari setelah hari terakhir (= 2026-10-31 17:30 UTC) TIDAK dihitung.
        $this->attempt($this->material('Lewat'), 100, '2026-11-01 00:30');

        $this->assertSame(2, $this->progress()['completed']);
    }

    public function test_more_materials_than_target_is_capped_at_target_and_100_percent(): void
    {
        foreach (range(1, 7) as $i) {
            $this->attempt($this->material("Materi {$i}"), 100, '2026-10-10 09:00');
        }

        $result = $this->progress();
        $this->assertSame(5, $result['completed']);
        $this->assertSame(100, $result['percentage']);
        $this->assertTrue($result['is_complete']);
        $this->assertSame('5 dari 5 materi', $result['summary']);
    }

    public function test_one_user_does_not_affect_another(): void
    {
        $other = $this->userWithRole('employee');
        $this->attempt($this->material(), 100, '2026-10-10 09:00', $other);

        $this->assertSame(0, $this->progress()['completed']);
        $this->assertSame(1, $this->progress($other)['completed']);
    }

    public function test_attempts_of_a_permanently_deleted_material_are_ignored(): void
    {
        $material = $this->material();
        $this->attempt($material, 100, '2026-10-10 09:00');
        $material->delete();

        $this->assertSame(0, $this->progress()['completed']);
    }

    public function test_video_post_tests_do_not_count_for_kpi(): void
    {
        $videoQuiz = Quiz::create([
            'title' => 'Post-Test Video',
            'type' => 'post_test',
            'related_type' => 'video',
            'related_id' => (string) Str::uuid(),
            'points_reward' => 25,
        ]);
        QuizAttempt::create([
            'quiz_id' => $videoQuiz->id,
            'user_id' => $this->employee->id,
            'score' => 100,
            'passed' => true,
            'points_earned' => 25,
            'attempted_at' => Carbon::parse('2026-10-10 09:00', 'Asia/Jakarta')->utc(),
        ]);

        $this->assertSame(0, $this->progress()['completed']);
    }

    public function test_post_test_with_201_questions_and_one_wrong_answer_is_not_100(): void
    {
        $material = $this->material('Banyak Soal');
        $quiz = $material->postTest()->firstOrFail();
        $answers = [];

        foreach (range(1, 201) as $i) {
            $question = QuizQuestion::create(['quiz_id' => $quiz->id, 'question_text' => "Soal {$i}", 'order_index' => $i, 'allow_multiple_answers' => false]);
            $correct = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'Benar', 'is_correct' => true]);
            $wrong = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'Salah', 'is_correct' => false]);
            $answers[$question->id] = $i === 201 ? $wrong->id : $correct->id;
        }

        UserLearningProgress::create(['user_id' => $this->employee->id, 'learning_material_id' => $material->id, 'progress_percent' => 100]);

        Livewire::actingAs($this->employee)
            ->test(PostTest::class, ['material' => $material])
            ->set('userAnswers', $answers)
            ->call('submitPostTest')
            ->assertSet('score', 99)
            ->assertSet('passed', false);

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail();
        $this->assertSame(99, $attempt->score);
        $this->assertFalse($attempt->passed);
    }

    public function test_kpi_settings_default_period_is_the_current_year_when_none_exists(): void
    {
        KpiSetting::query()->delete();

        $setting = KpiSetting::current();

        $this->assertSame(now('Asia/Jakarta')->startOfYear()->toDateString(), $setting->period_start->toDateString());
        $this->assertSame(now('Asia/Jakarta')->endOfYear()->toDateString(), $setting->period_end->toDateString());
        $this->assertSame(5, $setting->target_materials);
    }

    public function test_old_kpi_settings_columns_are_replaced_and_old_rows_get_a_safe_default(): void
    {
        $this->assertTrue(Schema::hasColumns('kpi_settings', ['period_start', 'period_end', 'target_materials']));
        foreach (['target_video_count', 'period_type', 'points_reward'] as $old) {
            $this->assertFalse(Schema::hasColumn('kpi_settings', $old), "Kolom {$old} seharusnya sudah di-drop.");
        }

        // Jalankan ulang migration dari skema lama untuk membuktikan backfill baris lama.
        $migration = require database_path('migrations/2026_09_24_000001_replace_kpi_settings_columns_with_material_period.php');
        $migration->down();
        DB::table('kpi_settings')->delete();
        DB::table('kpi_settings')->insert(['target_video_count' => 10, 'period_type' => 'monthly', 'points_reward' => 50, 'created_at' => now(), 'updated_at' => now()]);
        $migration->up();

        $row = DB::table('kpi_settings')->first();
        $this->assertSame(now('Asia/Jakarta')->startOfYear()->toDateString(), substr((string) $row->period_start, 0, 10));
        $this->assertSame(now('Asia/Jakarta')->endOfYear()->toDateString(), substr((string) $row->period_end, 0, 10));
        $this->assertSame(5, (int) $row->target_materials);
    }

    public function test_kpi_settings_form_validates_dates_and_target_and_is_admin_only(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = $this->userWithRole('admin');

        Livewire::actingAs($admin)->test(CreateKpiSetting::class)
            ->fillForm(['period_start' => '2026-10-31', 'period_end' => '2026-10-01', 'target_materials' => 5])
            ->call('create')
            ->assertHasFormErrors(['period_end']);

        Livewire::actingAs($admin)->test(CreateKpiSetting::class)
            ->fillForm(['period_start' => '2026-10-01', 'period_end' => '2026-10-31', 'target_materials' => 0])
            ->call('create')
            ->assertHasFormErrors(['target_materials']);

        Livewire::actingAs($admin)->test(CreateKpiSetting::class)
            ->fillForm(['period_start' => '2026-11-01', 'period_end' => '2026-11-30', 'target_materials' => 3])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertSame(3, KpiSetting::current()->target_materials);

        $this->actingAs($this->userWithRole('quality'))->get('/admin/kpi-settings/create')->assertForbidden();
    }
}
