<?php

namespace Tests\Feature;

use App\Livewire\Mission\Index as MissionIndex;
use App\Livewire\Mission\Show as MissionShow;
use App\Models\Division;
use App\Models\PointTransaction;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class StageNineMissionGameTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;

    protected User $employee;

    protected Quiz $caseStudy;

    protected Quiz $quickQuiz;

    protected QuizQuestion $csQuestion1;

    protected QuizOption $csOpt1Correct;

    protected QuizOption $csOpt1Wrong;

    protected QuizQuestion $qqQuestion1;

    protected QuizOption $qqOpt1Correct;

    protected QuizOption $qqOpt1Wrong;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->division = Division::firstOrFail();

        $this->employee = User::factory()->create([
            'division_id' => $this->division->id,
            'employee_id' => 'CPS-00888',
            'name' => 'Doni Operator Injeksi',
            'total_points' => 0,
            'level' => 1,
        ]);
        $this->employee->assignRole('employee');

        // Setup Case Study Mission
        $this->caseStudy = Quiz::create([
            'id' => (string) Str::uuid(),
            'title' => 'Investigasi Kontaminasi Raw Material Resin Lini Injeksi',
            'type' => 'mission_case_study',
            'related_type' => 'none',
            'related_id' => null,
            'points_reward' => 40,
            'description' => 'Skenario penanganan kontaminasi bintik hitam pada cetakan presisi.',
        ]);

        $this->csQuestion1 = QuizQuestion::create([
            'id' => (string) Str::uuid(),
            'quiz_id' => $this->caseStudy->id,
            'question_text' => 'Apa tindakan pertama saat mendeteksi cacat berulang pada komponen?',
            'order_index' => 1,
        ]);

        $this->csOpt1Correct = QuizOption::create([
            'id' => (string) Str::uuid(),
            'quiz_question_id' => $this->csQuestion1->id,
            'option_text' => 'Hentikan lini produksi sementara dan pasang tag HOLD',
            'is_correct' => true,
        ]);

        $this->csOpt1Wrong = QuizOption::create([
            'id' => (string) Str::uuid(),
            'quiz_question_id' => $this->csQuestion1->id,
            'option_text' => 'Lanjutkan produksi sampai akhir shift',
            'is_correct' => false,
        ]);

        // Setup Quick Quiz Mission
        $this->quickQuiz = Quiz::create([
            'id' => (string) Str::uuid(),
            'title' => 'Standar Operasional Keselamatan Listrik & Panel Mesin',
            'type' => 'mission_quiz',
            'related_type' => 'none',
            'related_id' => null,
            'points_reward' => 20,
            'description' => 'Kuis cepat evaluasi protokol keselamatan listrik manufaktur.',
        ]);

        $this->qqQuestion1 = QuizQuestion::create([
            'id' => (string) Str::uuid(),
            'quiz_id' => $this->quickQuiz->id,
            'question_text' => 'Alat ukur apa yang wajib digunakan untuk memastikan zero voltage?',
            'order_index' => 1,
        ]);

        $this->qqOpt1Correct = QuizOption::create([
            'id' => (string) Str::uuid(),
            'quiz_question_id' => $this->qqQuestion1->id,
            'option_text' => 'Multimeter / Voltmeter yang telah terkalibrasi',
            'is_correct' => true,
        ]);

        $this->qqOpt1Wrong = QuizOption::create([
            'id' => (string) Str::uuid(),
            'quiz_question_id' => $this->qqQuestion1->id,
            'option_text' => 'Testpen obeng biasa',
            'is_correct' => false,
        ]);
    }

    /**
     * DoD #1: Feedback instan tanpa reload (perubahan warna teks/ikon, tanpa confetti).
     */
    public function test_instant_feedback_without_reload_updates_visual_state(): void
    {
        // 1. Buka halaman show kuis
        $response = $this->actingAs($this->employee)->get(route('missions.show', $this->quickQuiz->id));
        $response->assertStatus(200);

        // 2. Uji pilihan SALAH -> feedback instan (merah, label Kurang Tepat)
        Livewire::actingAs($this->employee)
            ->test(MissionShow::class, ['quiz' => $this->quickQuiz])
            ->call('selectOption', $this->qqQuestion1->id, $this->qqOpt1Wrong->id)
            ->assertSee('Kurang Tepat')
            ->assertSeeHtml('text-red-700')
            ->assertDontSeeHtml('canvas-confetti') // Bebas confetti
            ->assertSet('evaluatedAnswers.'.$this->qqQuestion1->id.'.is_correct', false);

        // 3. Uji ubah ke pilihan BENAR -> feedback instan berganti seketika (hijau brand, label Benar)
        Livewire::actingAs($this->employee)
            ->test(MissionShow::class, ['quiz' => $this->quickQuiz])
            ->call('selectOption', $this->qqQuestion1->id, $this->qqOpt1Correct->id)
            ->assertSee('Benar')
            ->assertSeeHtml('text-brand-dark')
            ->assertDontSee('Kurang Tepat')
            ->assertSet('evaluatedAnswers.'.$this->qqQuestion1->id.'.is_correct', true);
    }

    /**
     * DoD #2: Poin tercatat lewat point_transactions (source_type = mission_completed).
     */
    public function test_points_are_recorded_via_point_transactions_ledger(): void
    {
        $this->assertEquals(0, $this->employee->total_points);

        // Kerjakan kuis dengan jawaban benar dan submit
        Livewire::actingAs($this->employee)
            ->test(MissionShow::class, ['quiz' => $this->caseStudy])
            ->call('selectOption', $this->csQuestion1->id, $this->csOpt1Correct->id)
            ->call('submitQuiz')
            ->assertSet('passed', true)
            ->assertSet('pointsEarned', 40)
            ->assertSee('+40 Poin')
            ->assertSee('telah berhasil tercatat');

        // Verifikasi transaksi masuk ke point_transactions
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->employee->id,
            'points' => 40,
            'source_type' => 'mission_completed',
            'source_id' => $this->caseStudy->id,
        ]);

        // Verifikasi riwayat percobaan di quiz_attempts
        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $this->caseStudy->id,
            'user_id' => $this->employee->id,
            'score' => 100,
            'passed' => true,
            'points_earned' => 40,
        ]);

        // Verifikasi PointTransactionObserver menyinkronkan total_points user
        $this->employee->refresh();
        $this->assertEquals(40, $this->employee->total_points);
    }

    /**
     * DoD #3: Retry policy ditandai TODO, bukan diasumsikan, dan poin tidak bertambah ganda.
     */
    public function test_retry_policy_marked_with_todo_and_prevents_duplicate_points(): void
    {
        // 1. Cek penanda TODO di kode backend App\Livewire\Mission\Show
        $showCode = file_get_contents(app_path('Livewire/Mission/Show.php'));
        $this->assertStringContainsString('TODO: Menunggu keputusan PRD §5.3', $showCode);
        $this->assertStringContainsString('Poin 4: Kebijakan retry kuis/misi', $showCode);

        // 2. Simulasikan kuis sudah pernah lulus
        PointTransaction::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'points' => 20,
            'source_type' => 'mission_completed',
            'source_id' => $this->quickQuiz->id,
            'description' => "Menyelesaikan Misi: {$this->quickQuiz->title}",
            'created_at' => now(),
        ]);

        QuizAttempt::create([
            'id' => (string) Str::uuid(),
            'quiz_id' => $this->quickQuiz->id,
            'user_id' => $this->employee->id,
            'score' => 100,
            'passed' => true,
            'points_earned' => 20,
            'attempted_at' => now(),
        ]);

        // 3. User mengulang (retry) kuis
        Livewire::actingAs($this->employee)
            ->test(MissionShow::class, ['quiz' => $this->quickQuiz])
            ->call('resetQuiz')
            ->call('selectOption', $this->qqQuestion1->id, $this->qqOpt1Correct->id)
            ->call('submitQuiz')
            ->assertSee('[Menunggu Keputusan PRD §5.3: Kebijakan Retry Kuis]')
            ->assertSee('sudah pernah Anda klaim')
            ->assertSet('pointsEarned', 0);

        // Pastikan point_transactions TETAP 1 baris (tidak bertambah ganda)
        $this->assertEquals(
            1,
            PointTransaction::where('user_id', $this->employee->id)
                ->where('source_type', 'mission_completed')
                ->where('source_id', $this->quickQuiz->id)
                ->count()
        );
    }

    /**
     * DoD #4: Tidak ada efek visual berlebihan (sesuai Anti-AI-Slop).
     */
    public function test_anti_ai_slop_compliance_and_catalog_rendering(): void
    {
        // 1. Katalog Mission & Game memuat kedua tipe misi
        Livewire::actingAs($this->employee)
            ->test(MissionIndex::class)
            ->assertSee('Investigasi Kontaminasi Raw Material Resin Lini Injeksi')
            ->assertSee('Standar Operasional Keselamatan Listrik & Panel Mesin')
            ->assertSee('+40 Poin')
            ->assertSee('+20 Poin')
            ->assertDontSee('confetti')
            ->assertDontSee('fireworks')
            ->assertDontSee('→'); // Checklist Anti-AI-Slop: Tidak ada panah di tombol

        // 2. Filter tipe Quiz Cepat
        Livewire::actingAs($this->employee)
            ->test(MissionIndex::class)
            ->set('selectedType', 'mission_quiz')
            ->assertSee('Standar Operasional Keselamatan Listrik & Panel Mesin')
            ->assertDontSee('Investigasi Kontaminasi Raw Material Resin Lini Injeksi');

        // 3. Filter tipe Studi Kasus
        Livewire::actingAs($this->employee)
            ->test(MissionIndex::class)
            ->set('selectedType', 'mission_case_study')
            ->assertSee('Investigasi Kontaminasi Raw Material Resin Lini Injeksi')
            ->assertDontSee('Standar Operasional Keselamatan Listrik & Panel Mesin');
    }
}
