<?php

namespace Tests\Feature\Kpi;

use App\Livewire\Learning\PostTest;
use App\Models\Division;
use App\Models\KpiSetting;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\PointTransaction;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserKpiYearly;
use App\Models\UserLearningProgress;
use App\Services\KpiContributionService;
use Carbon\Carbon;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * Poin CPS ERA dari materi (Jalur B, aturan owner): tepat 1 poin saat progres periode PERTAMA KALI
 * mencapai target; tidak ada poin materi lagi di periode itu; batas 3 poin/tahun (Jalur A + B) tetap;
 * poin dicatat ke tahun kalender saat poin diberikan.
 */
class MaterialKpiPointTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private LearningCategory $category;

    private KpiContributionService $service;

    private int $materialCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->employee = User::factory()->create(['division_id' => Division::where('name', 'Produksi')->value('id')]);
        $this->employee->assignRole('employee');
        $this->category = LearningCategory::create(['name' => 'Kategori KPI', 'created_by' => $this->employee->id]);
        $this->service = app(KpiContributionService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function period(string $start, string $end, int $target = 5): KpiSetting
    {
        return KpiSetting::create(['period_start' => $start, 'period_end' => $end, 'target_materials' => $target]);
    }

    private function material(): LearningMaterial
    {
        $this->materialCounter++;

        $material = LearningMaterial::create([
            'learning_category_id' => $this->category->id,
            'title' => "Materi {$this->materialCounter}",
            'type' => 'artikel',
            'status' => 'published',
            'created_by' => $this->employee->id,
        ]);

        Quiz::create([
            'title' => "Post-Test Materi {$this->materialCounter}",
            'type' => 'post_test',
            'related_type' => 'learning_material',
            'related_id' => $material->id,
            'points_reward' => 0,
        ]);

        return $material;
    }

    /**
     * Simulasikan lulus post-test 100% pada waktu WIB tertentu, persis seperti alur PostTest:
     * attempt disimpan dulu, lalu KpiContributionService dipanggil.
     */
    private function pass(LearningMaterial $material, string $wib): QuizAttempt
    {
        Carbon::setTestNow(Carbon::parse($wib, 'Asia/Jakarta')->utc());

        $attempt = QuizAttempt::create([
            'quiz_id' => $material->postTest()->firstOrFail()->id,
            'user_id' => $this->employee->id,
            'score' => 100,
            'passed' => true,
            'points_earned' => 0,
            'attempted_at' => now(),
        ]);

        $this->service->recordMaterialCompletion($this->employee, $material, 100, $attempt);

        return $attempt;
    }

    private function materialPoints(): int
    {
        return (int) PointTransaction::where('user_id', $this->employee->id)
            ->where('ledger_type', PointTransaction::LEDGER_POIN_CPS_ERA)
            ->where('source_type', 'kpi_materi_bundle_completed')
            ->sum('points');
    }

    private function yearly(int $year): ?UserKpiYearly
    {
        return UserKpiYearly::where('user_id', $this->employee->id)->where('period_year', $year)->first();
    }

    public function test_reaching_the_target_awards_exactly_one_point(): void
    {
        $this->period('2026-10-01', '2026-10-31');

        foreach (range(1, 4) as $day) {
            $this->pass($this->material(), "2026-10-0{$day} 09:00");
        }
        $this->assertSame(0, $this->materialPoints());

        $this->pass($this->material(), '2026-10-05 09:00');

        $this->assertSame(1, $this->materialPoints());
        $this->assertSame(1, $this->yearly(2026)->poin_cps_era_earned);
        $this->assertSame(1, $this->yearly(2026)->poin_from_materi);
    }

    public function test_no_more_material_points_in_the_same_period_after_the_target(): void
    {
        $this->period('2026-10-01', '2026-10-31');
        $materials = [];

        foreach (range(1, 5) as $day) {
            $materials[] = $material = $this->material();
            $this->pass($material, "2026-10-0{$day} 09:00");
        }

        // Materi ke-6..10 dan pengulangan materi pertama di periode yang sama.
        foreach (range(10, 14) as $day) {
            $this->pass($this->material(), "2026-10-{$day} 09:00");
        }
        $this->pass($materials[0], '2026-10-20 09:00');

        $this->assertSame(1, $this->materialPoints());
    }

    public function test_running_the_award_twice_for_the_same_attempt_gives_one_point(): void
    {
        $this->period('2026-10-01', '2026-10-31');

        foreach (range(1, 4) as $day) {
            $this->pass($this->material(), "2026-10-0{$day} 09:00");
        }
        $fifth = $this->material();
        $attempt = $this->pass($fifth, '2026-10-05 09:00');

        $this->service->recordMaterialCompletion($this->employee, $fifth, 100, $attempt);

        $this->assertSame(1, $this->materialPoints());
        $this->assertSame(1, $this->yearly(2026)->poin_cps_era_earned);
    }

    public function test_yearly_cap_of_three_points_across_both_tracks_is_respected(): void
    {
        $this->period('2026-10-01', '2026-10-31');
        UserKpiYearly::create([
            'user_id' => $this->employee->id,
            'period_year' => 2026,
            'materials_completed_count' => 0,
            'poin_cps_era_earned' => 3,
            'poin_from_ba' => 3,
            'poin_from_materi' => 0,
        ]);

        foreach (range(1, 5) as $day) {
            $this->pass($this->material(), "2026-10-0{$day} 09:00");
        }

        $this->assertSame(0, $this->materialPoints());
        $this->assertSame(3, $this->yearly(2026)->poin_cps_era_earned);
        $this->assertSame(5, QuizAttempt::where('user_id', $this->employee->id)->where('passed', true)->count());
    }

    public function test_a_new_period_starts_from_zero_and_can_award_again(): void
    {
        $this->period('2026-10-01', '2026-10-31');
        foreach (range(1, 7) as $day) {
            $this->pass($this->material(), "2026-10-0{$day} 09:00");
        }
        $this->assertSame(1, $this->materialPoints());

        $this->period('2026-11-01', '2026-11-30');
        foreach (range(1, 4) as $day) {
            $this->pass($this->material(), "2026-11-0{$day} 09:00");
        }
        // Progres periode baru mulai dari 0: 4 materi belum cukup.
        $this->assertSame(1, $this->materialPoints());

        $this->pass($this->material(), '2026-11-05 09:00');
        $this->assertSame(2, $this->materialPoints());
        $this->assertSame(2, $this->yearly(2026)->poin_from_materi);
    }

    public function test_cross_year_period_records_the_point_in_the_calendar_year_it_is_awarded(): void
    {
        $this->period('2026-12-01', '2027-01-31');

        foreach (range(1, 4) as $day) {
            $this->pass($this->material(), "2026-12-0{$day} 09:00");
        }
        $this->pass($this->material(), '2027-01-10 09:00');

        $this->assertSame(1, $this->materialPoints());
        $this->assertSame(1, $this->yearly(2027)->poin_cps_era_earned);
        $this->assertSame(0, (int) ($this->yearly(2026)?->poin_cps_era_earned ?? 0));
    }

    public function test_passing_outside_the_active_period_does_not_award(): void
    {
        $this->period('2026-10-01', '2026-10-31');

        foreach (range(1, 5) as $day) {
            $this->pass($this->material(), "2026-11-0{$day} 09:00");
        }

        $this->assertSame(0, $this->materialPoints());
    }

    public function test_xp_and_the_legacy_counter_are_not_changed_by_the_kpi(): void
    {
        $this->period('2026-10-01', '2026-10-31');
        UserKpiYearly::create([
            'user_id' => $this->employee->id,
            'period_year' => 2026,
            'materials_completed_count' => 3,
            'poin_cps_era_earned' => 1,
            'poin_from_ba' => 0,
            'poin_from_materi' => 1,
        ]);
        $xpBefore = (int) $this->employee->fresh()->xp;

        foreach (range(1, 5) as $day) {
            $this->pass($this->material(), "2026-10-0{$day} 09:00");
        }

        $this->assertSame($xpBefore, (int) $this->employee->fresh()->xp);
        // Counter lama berhenti di-update; riwayat poin lama tetap, hanya bertambah 1 dari target baru.
        $this->assertSame(3, $this->yearly(2026)->materials_completed_count);
        $this->assertSame(2, $this->yearly(2026)->poin_from_materi);
        $this->assertSame(2, $this->yearly(2026)->poin_cps_era_earned);
    }

    public function test_post_test_result_is_kept_when_awarding_points_fails(): void
    {
        $this->period(now('Asia/Jakarta')->startOfYear()->toDateString(), now('Asia/Jakarta')->endOfYear()->toDateString());
        $material = $this->material();
        $quiz = $material->postTest()->firstOrFail();
        $question = QuizQuestion::create(['quiz_id' => $quiz->id, 'question_text' => 'Soal', 'order_index' => 1, 'allow_multiple_answers' => false]);
        $correct = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'Benar', 'is_correct' => true]);
        QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'Salah', 'is_correct' => false]);
        UserLearningProgress::create(['user_id' => $this->employee->id, 'learning_material_id' => $material->id, 'progress_percent' => 100]);

        $this->mock(KpiContributionService::class)
            ->shouldReceive('recordMaterialCompletion')
            ->andThrow(new RuntimeException('Simulasi kegagalan pencatatan poin'));

        Livewire::actingAs($this->employee)
            ->test(PostTest::class, ['material' => $material])
            ->set('userAnswers', [$question->id => $correct->id])
            ->call('submitPostTest')
            ->assertSet('isSubmitted', true)
            ->assertSet('passed', true)
            ->assertSet('score', 100);

        $this->assertSame(1, QuizAttempt::where('quiz_id', $quiz->id)->where('passed', true)->count());
    }
}
