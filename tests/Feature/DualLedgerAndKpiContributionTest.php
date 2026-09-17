<?php

namespace Tests\Feature;

use App\Livewire\Learning\PostTest;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\PointTransaction;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserKpiYearly;
use App\Models\UserLearningProgress;
use App\Services\BaIncidentService;
use App\Services\KpiContributionService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DualLedgerAndKpiContributionTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;

    protected User $admin;

    protected User $supervisor;

    protected User $employee;

    protected LearningCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->division = Division::firstOrFail();

        $this->admin = User::factory()->create(['division_id' => $this->division->id]);
        $this->admin->assignRole('admin');

        $this->supervisor = User::factory()->create(['division_id' => $this->division->id]);
        $this->supervisor->assignRole('supervisor');

        $this->employee = User::factory()->create(['division_id' => $this->division->id]);
        $this->employee->assignRole('employee');

        $this->category = LearningCategory::create([
            'name' => 'Manufaktur & Kaizen',
            'created_by' => $this->admin->id,
        ]);
    }

    /**
     * 1. Post-test bisa disubmit berkali-kali tanpa limit, dan response API
     *    tidak pernah mengandung field is_correct dari opsi manapun.
     */
    public function test_post_test_allows_unlimited_attempts_and_never_exposes_is_correct(): void
    {
        $material = LearningMaterial::create([
            'learning_category_id' => $this->category->id,
            'title' => 'Materi Kalibrasi Torque',
            'type' => 'video',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        $quiz = Quiz::create([
            'title' => 'Post-Test: Materi Kalibrasi Torque',
            'type' => 'post_test',
            'related_type' => 'learning_material',
            'related_id' => $material->id,
            'points_reward' => 50,
        ]);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Berapa toleransi deviasi kunci torsi?',
            'order_index' => 1,
            'allow_multiple_answers' => false,
        ]);

        $optCorrect = QuizOption::create([
            'quiz_question_id' => $question->id,
            'option_text' => '+- 4%',
            'is_correct' => true,
        ]);

        $optWrong = QuizOption::create([
            'quiz_question_id' => $question->id,
            'option_text' => '+- 20%',
            'is_correct' => false,
        ]);

        // Submit attempt 1 (gagal)
        $res1 = $this->actingAs($this->employee)->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                $question->id => $optWrong->id,
            ],
        ]);
        $res1->assertOk();
        $this->assertFalse($res1->json('data.passed'));
        $this->assertStringNotContainsString('is_correct', $res1->getContent());

        // Submit attempt 2 (gagal lagi)
        $res2 = $this->actingAs($this->employee)->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                $question->id => $optWrong->id,
            ],
        ]);
        $res2->assertOk();
        $this->assertFalse($res2->json('data.passed'));
        $this->assertStringNotContainsString('is_correct', $res2->getContent());

        // Submit attempt 3 (sebelumnya sistem lama memblokir di attempt > 2, sekarang WAJIB boleh)
        $res3 = $this->actingAs($this->employee)->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                $question->id => $optCorrect->id,
            ],
        ]);
        $res3->assertOk();
        $this->assertTrue($res3->json('data.passed'));
        $this->assertSame(100, $res3->json('data.score'));
        $this->assertStringNotContainsString('is_correct', $res3->getContent());

        // Cek response GET detail quiz juga tidak mengandung is_correct
        $resShow = $this->actingAs($this->employee)->getJson("/api/quizzes/{$quiz->id}");
        $resShow->assertOk();
        $this->assertStringNotContainsString('is_correct', $resShow->getContent());

        // Buat progress 100% agar lolos gating PostTest Livewire component
        UserLearningProgress::create([
            'user_id' => $this->employee->id,
            'learning_material_id' => $material->id,
            'progress_percent' => 100,
        ]);

        // Verifikasi juga via Livewire PostTest component
        Livewire::actingAs($this->employee)
            ->test(PostTest::class, ['material' => $material])
            ->set('userAnswers', [$question->id => $optCorrect->id])
            ->call('submitPostTest')
            ->assertSet('isSubmitted', true)
            ->assertSet('score', 100)
            ->assertSet('passed', true);
    }

    /**
     * 2. Post-test dengan allow_multiple_answers=true hanya dianggap benar kalau
     *    kombinasi opsi yang dipilih PERSIS sama dengan opsi yang is_correct=true.
     */
    public function test_multi_select_question_scoring_requires_exact_match_of_correct_options(): void
    {
        $material = LearningMaterial::create([
            'learning_category_id' => $this->category->id,
            'title' => 'SOP Safety Kelistrikan',
            'type' => 'dokumen',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        $quiz = Quiz::create([
            'title' => 'Post-Test: SOP Safety Kelistrikan',
            'type' => 'post_test',
            'related_type' => 'learning_material',
            'related_id' => $material->id,
            'points_reward' => 0,
        ]);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Alat pelindung diri apa saja yang wajib saat bekerja di panel MV? (Pilih semua yang benar)',
            'order_index' => 1,
            'allow_multiple_answers' => true,
        ]);

        $optA = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'Sarung tangan dielektrik 20kV', 'is_correct' => true]);
        $optB = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'Face shield arc-flash', 'is_correct' => true]);
        $optC = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'Sandal karet biasa', 'is_correct' => false]);
        $optD = QuizOption::create(['quiz_question_id' => $question->id, 'option_text' => 'Perhiasan logam', 'is_correct' => false]);

        // Skenario A: Hanya pilih 1 dari 2 yang benar -> SALAH (Skor 0%)
        $resPartially = $this->actingAs($this->employee)->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                $question->id => [$optA->id],
            ],
        ]);
        $resPartially->assertOk();
        $this->assertFalse($resPartially->json('data.passed'));
        $this->assertSame(0, $resPartially->json('data.score'));

        // Skenario B: Pilih 2 yang benar TAPI ikut memilih 1 yang salah -> SALAH (Skor 0%)
        $resWithWrong = $this->actingAs($this->employee)->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                $question->id => [$optA->id, $optB->id, $optC->id],
            ],
        ]);
        $resWithWrong->assertOk();
        $this->assertFalse($resWithWrong->json('data.passed'));
        $this->assertSame(0, $resWithWrong->json('data.score'));

        // Skenario C: Pilih PERSIS semua yang benar (optA & optB) -> BENAR (Skor 100%)
        $resExact = $this->actingAs($this->employee)->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'answers' => [
                $question->id => [$optA->id, $optB->id],
            ],
        ]);
        $resExact->assertOk();
        $this->assertTrue($resExact->json('data.passed'));
        $this->assertSame(100, $resExact->json('data.score'));
    }

    /**
     * 3. Menyelesaikan materi ke-5 (dengan score 100% semua) memicu insert point_transactions
     *    ledger_type='poin_cps_era' DAN increment user_kpi_yearly dengan benar, lalu counter
     *    materials_completed_count reset ke 0.
     */
    public function test_completing_5_materials_with_100_percent_awards_cps_era_point_and_resets_counter(): void
    {
        $kpiService = app(KpiContributionService::class);
        $currentYear = (int) now()->year;

        // Buat 5 materi terpisah
        $materials = [];
        for ($i = 1; $i <= 5; $i++) {
            $materials[] = LearningMaterial::create([
                'learning_category_id' => $this->category->id,
                'title' => "Modul Kualifikasi {$i}",
                'type' => 'dokumen',
                'status' => 'published',
                'created_by' => $this->admin->id,
            ]);
        }

        // Selesaikan materi 1 s.d. 4 dengan score 100%
        for ($i = 0; $i < 4; $i++) {
            $kpiService->recordMaterialCompletion($this->employee, $materials[$i], 100);
        }

        $kpi = UserKpiYearly::where('user_id', $this->employee->id)->where('period_year', $currentYear)->first();
        $this->assertNotNull($kpi);
        $this->assertSame(4, $kpi->materials_completed_count);
        $this->assertSame(0, $kpi->poin_cps_era_earned);
        $this->assertSame(0, $kpi->poin_from_materi);

        // Pastikan belum ada point_transactions dengan ledger_type='poin_cps_era'
        $this->assertSame(0, PointTransaction::where('user_id', $this->employee->id)->where('ledger_type', 'poin_cps_era')->count());

        // Selesaikan materi ke-5 dengan score 100%
        $kpiService->recordMaterialCompletion($this->employee, $materials[4], 100);

        $kpi->refresh();
        // Counter reset ke 0 setelah mencapai kelipatan 5
        $this->assertSame(0, $kpi->materials_completed_count);
        // Poin bertambah 1
        $this->assertSame(1, $kpi->poin_cps_era_earned);
        $this->assertSame(1, $kpi->poin_from_materi);

        // Verifikasi transaksi poin dibuat dengan ledger_type='poin_cps_era'
        $transaction = PointTransaction::where('user_id', $this->employee->id)
            ->where('ledger_type', 'poin_cps_era')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertSame(1, $transaction->points);
        $this->assertSame('kpi_materi_bundle_completed', $transaction->source_type);
    }

    /**
     * 4. Approve BA memicu insert point_transactions ledger_type='poin_cps_era',
     *    source_type='ba_video_approved', SELAMA cap tahunan (3) belum tercapai.
     *    Sekaligus memverifikasi pipeline otomatis kandidat materi Learning.
     */
    public function test_ba_approval_awards_cps_era_point_and_creates_candidate_learning_material(): void
    {
        $incident = BaIncident::create([
            'id' => (string) Str::uuid(),
            'nomor_ba' => 'BA/2026/09/001',
            'division_id' => $this->division->id,
            'title' => 'Kerusakan Roll Press 02',
            'deskripsi_masalah' => 'Roll press macet karena bearing aus.',
            'created_by' => $this->employee->id,
            'status' => 'submitted',
            'video_file_url' => '/uploads/ba-videos/roll-press.mp4',
        ]);

        $baService = app(BaIncidentService::class);
        $approvedBa = $baService->approve($incident, $this->supervisor, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => 'Penggantian bearing tipe 6205RS tuntas dan beroperasi normal.',
        ]);

        $this->assertSame('approved', $approvedBa->status);

        // Verifikasi point_transactions dibuat dengan ledger_type='poin_cps_era'
        $tx = PointTransaction::where('user_id', $this->employee->id)
            ->where('ledger_type', 'poin_cps_era')
            ->where('source_type', 'ba_video_approved')
            ->first();

        $this->assertNotNull($tx);
        $this->assertSame(1, $tx->points);
        $this->assertSame($incident->id, $tx->source_id);

        // Verifikasi agregasi user_kpi_yearly
        $kpi = UserKpiYearly::where('user_id', $this->employee->id)
            ->where('period_year', (int) now()->year)
            ->first();

        $this->assertNotNull($kpi);
        $this->assertSame(1, $kpi->poin_cps_era_earned);
        $this->assertSame(1, $kpi->poin_from_ba);

        // Verifikasi pipeline: 1 baris LearningMaterial baru bertipe video dan status candidate
        $materialCandidate = LearningMaterial::where('source_ba_id', $incident->id)->first();
        $this->assertNotNull($materialCandidate);
        $this->assertSame('candidate', $materialCandidate->status);
        $this->assertSame('video', $materialCandidate->type);
        $this->assertSame('Video Penanganan: BA/2026/09/001', $materialCandidate->title);
    }

    /**
     * 5. Setelah poin_cps_era_earned mencapai 3 di tahun berjalan, baik lewat Jalur A maupun
     *    Jalur B, tidak ada penambahan poin CPS ERA lagi sampai tahun berikutnya -- tapi
     *    approve BA / lulus post-test tetap berjalan normal tanpa poin tambahan (bukan ditolak).
     */
    public function test_cps_era_points_capped_at_3_per_year_across_both_tracks_without_rejection(): void
    {
        $currentYear = (int) now()->year;

        // Set user sudah memiliki 3 Poin CPS ERA tahun ini (mencapai batas tahunan)
        $kpi = UserKpiYearly::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'period_year' => $currentYear,
            'materials_completed_count' => 4,
            'poin_cps_era_earned' => 3,
            'poin_from_ba' => 2,
            'poin_from_materi' => 1,
        ]);

        $initialTxCount = PointTransaction::where('user_id', $this->employee->id)
            ->where('ledger_type', 'poin_cps_era')
            ->count();

        // Jalur A Test: Approve BA baru saat cap sudah 3
        $incident = BaIncident::create([
            'id' => (string) Str::uuid(),
            'nomor_ba' => 'BA/2026/09/999',
            'division_id' => $this->division->id,
            'title' => 'Insiden Sensor Optical',
            'deskripsi_masalah' => 'Sensor kotor tersumbat debu.',
            'created_by' => $this->employee->id,
            'status' => 'submitted',
            'video_file_url' => '/videos/sensor.mp4',
        ]);

        $baService = app(BaIncidentService::class);
        $approvedBa = $baService->approve($incident, $this->supervisor, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => 'Sensor dibersihkan dan dipasang penutup filter udara.',
        ]);

        // BA TETAP berhasil disetujui (tidak error/ditolak)
        $this->assertSame('approved', $approvedBa->status);

        // Tapi TIDAK ada penambahan transaksi Poin CPS ERA
        $this->assertSame(
            $initialTxCount,
            PointTransaction::where('user_id', $this->employee->id)->where('ledger_type', 'poin_cps_era')->count()
        );
        $kpi->refresh();
        $this->assertSame(3, $kpi->poin_cps_era_earned);

        // Jalur B Test: Selesaikan materi ke-5 (membuat materials_completed_count mencapai 5)
        $material5 = LearningMaterial::create([
            'learning_category_id' => $this->category->id,
            'title' => 'Modul Final Ke-5',
            'type' => 'dokumen',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        $kpiService = app(KpiContributionService::class);
        $kpiService->recordMaterialCompletion($this->employee, $material5, 100);

        $kpi->refresh();
        // Counter tetap reset ke 0 sesuai instruksi interim PRD
        $this->assertSame(0, $kpi->materials_completed_count);
        // Tapi poin_cps_era_earned tetap mentok di 3
        $this->assertSame(3, $kpi->poin_cps_era_earned);
        // Dan tidak ada insert poin transaksi baru
        $this->assertSame(
            $initialTxCount,
            PointTransaction::where('user_id', $this->employee->id)->where('ledger_type', 'poin_cps_era')->count()
        );
    }

    /**
     * 6. Materi dengan status='candidate' tidak muncul di listing Learning Employee biasa.
     */
    public function test_candidate_learning_materials_do_not_appear_in_employee_listing(): void
    {
        $published = LearningMaterial::create([
            'learning_category_id' => $this->category->id,
            'title' => 'Materi SOP Standard Operasi',
            'type' => 'dokumen',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        $candidate = LearningMaterial::create([
            'learning_category_id' => $this->category->id,
            'title' => 'Materi Candidate Video BA',
            'type' => 'video',
            'status' => 'candidate',
            'created_by' => $this->admin->id,
        ]);

        // Cek via API listing kategori untuk employee
        $res = $this->actingAs($this->employee)->getJson("/api/learning-categories/{$this->category->id}/materials");
        $res->assertOk();

        $titles = collect($res->json('data.materials'))->pluck('title');
        $this->assertTrue($titles->contains('Materi SOP Standard Operasi'));
        $this->assertFalse($titles->contains('Materi Candidate Video BA'));

        // Cek via scopePublished model
        $publishedCount = LearningMaterial::published()->count();
        $this->assertSame(1, $publishedCount);
    }

    /**
     * 7. users.xp terisi benar dari sumber Mission & Game dan XP opsional Learning material,
     *    TIDAK tercampur dengan poin_cps_era manapun.
     */
    public function test_users_xp_only_tallies_xp_ledger_and_never_mixes_with_cps_era_points(): void
    {
        $this->assertSame(0, (int) $this->employee->fresh()->xp);

        // 1. Tambah transaksi Mission & Game (ledger_type = 'xp')
        PointTransaction::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'ledger_type' => 'xp',
            'points' => 30,
            'source_type' => 'mission_completed',
            'description' => 'Menyelesaikan Misi Quiz',
            'created_at' => now(),
        ]);

        $this->assertSame(30, (int) $this->employee->fresh()->xp);

        // 2. Tambah transaksi XP opsional Learning material (ledger_type = 'xp')
        PointTransaction::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'ledger_type' => 'xp',
            'points' => 20,
            'source_type' => 'learning_material_xp',
            'description' => 'Mempelajari modul manufaktur',
            'created_at' => now(),
        ]);

        $this->assertSame(50, (int) $this->employee->fresh()->xp);

        // 3. Tambah transaksi Poin CPS ERA (ledger_type = 'poin_cps_era')
        PointTransaction::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'ledger_type' => 'poin_cps_era',
            'points' => 1,
            'source_type' => 'ba_video_approved',
            'description' => 'Poin CPS ERA dari BA',
            'created_at' => now(),
        ]);

        // CRITICAL CHECK: users.xp HARUS TETAP 50, BUKAN 51!
        $this->assertSame(50, (int) $this->employee->fresh()->xp);
    }
}
