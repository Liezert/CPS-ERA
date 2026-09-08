<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;

    protected User $admin;

    protected User $quality;

    protected User $employee;

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

        $this->employee = User::factory()->create(['division_id' => $this->division->id]);
        $this->employee->assignRole('employee');
    }

    public function test_quiz_detail_does_not_expose_is_correct_field(): void
    {
        $quiz = Quiz::create([
            'title' => 'Misi Pengetahuan Mesin 1',
            'type' => 'mission_quiz',
            'points_reward' => 30,
        ]);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Apa fungsi sensor proximity?',
            'order_index' => 1,
        ]);

        QuizOption::create([
            'quiz_question_id' => $question->id,
            'option_text' => 'Mendeteksi keberadaan objek tanpa kontak fisik',
            'is_correct' => true,
        ]);

        QuizOption::create([
            'quiz_question_id' => $question->id,
            'option_text' => 'Mengatur suhu ruangan',
            'is_correct' => false,
        ]);

        $response = $this->actingAs($this->employee)->getJson("/api/quizzes/{$quiz->id}");

        $response->assertOk();
        $data = $response->json('data');

        $this->assertNotEmpty($data['questions']);
        $this->assertCount(2, $data['questions'][0]['options']);

        // Pastikan field is_correct tidak pernah ada di level response manapun
        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('is_correct', $responseContent);

        foreach ($data['questions'][0]['options'] as $option) {
            $this->assertArrayNotHasKey('is_correct', $option);
            $this->assertArrayHasKey('id', $option);
            $this->assertArrayHasKey('option_text', $option);
        }
    }

    public function test_submit_attempt_with_correct_answers_passes_and_awards_points(): void
    {
        $initialPoints = $this->employee->total_points;

        $quiz = Quiz::create([
            'title' => 'Misi Studi Kasus Keselamatan Kerja',
            'type' => 'mission_case_study',
            'points_reward' => 50,
        ]);

        $q1 = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Langkah pertama saat alarm kebakaran berbunyi?',
            'order_index' => 1,
        ]);

        $optCorrect1 = QuizOption::create([
            'quiz_question_id' => $q1->id,
            'option_text' => 'Evakuasi melalui jalur darurat',
            'is_correct' => true,
        ]);

        QuizOption::create([
            'quiz_question_id' => $q1->id,
            'option_text' => 'Mengambil barang berharga dulu',
            'is_correct' => false,
        ]);

        // Submit jawaban benar via selected_option_id
        $response = $this->actingAs($this->employee)->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'selected_option_id' => $optCorrect1->id,
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('data.passed'));
        $this->assertSame(100, $response->json('data.score'));
        $this->assertSame(50, $response->json('data.points_earned'));
        $this->assertSame($initialPoints + 50, $response->json('data.total_points'));

        // Cek tabel quiz_attempts
        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'user_id' => $this->employee->id,
            'passed' => true,
            'points_earned' => 50,
        ]);

        // Cek tabel point_transactions
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->employee->id,
            'points' => 50,
            'source_type' => 'mission_completed',
            'source_id' => $quiz->id,
        ]);

        $this->assertSame($initialPoints + 50, $this->employee->fresh()->total_points);
    }

    public function test_submit_attempt_with_forged_score_is_recalculated_server_side(): void
    {
        $quiz = Quiz::create([
            'title' => 'Quiz Presisi',
            'type' => 'mission_quiz',
            'points_reward' => 40,
        ]);

        $q1 = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Berapa voltase tegangan industri 3 fasa?',
            'order_index' => 1,
        ]);

        QuizOption::create([
            'quiz_question_id' => $q1->id,
            'option_text' => '380 Volt',
            'is_correct' => true,
        ]);

        $optWrong = QuizOption::create([
            'quiz_question_id' => $q1->id,
            'option_text' => '12 Volt',
            'is_correct' => false,
        ]);

        // Client mencoba memalsukan score=100 dan passed=true padahal opsi salah
        $response = $this->actingAs($this->employee)->postJson("/api/quizzes/{$quiz->id}/attempt", [
            'selected_option_id' => $optWrong->id,
            'score' => 100,
            'passed' => true,
            'points_earned' => 40,
        ]);

        $response->assertOk();
        // Server WAJIB menghitung ulang dan menghasilkan passed=false, score=0
        $this->assertFalse($response->json('data.passed'));
        $this->assertSame(0, $response->json('data.score'));
        $this->assertSame(0, $response->json('data.points_earned'));

        // Poin TIDAK boleh masuk ke point_transactions
        $this->assertDatabaseMissing('point_transactions', [
            'user_id' => $this->employee->id,
            'source_id' => $quiz->id,
        ]);

        // Attempt tersimpan dengan passed=false
        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'user_id' => $this->employee->id,
            'passed' => false,
            'points_earned' => 0,
        ]);
    }

    public function test_post_test_appears_in_learning_material_only_after_100_percent(): void
    {
        $category = LearningCategory::create([
            'name' => 'Kategori Automation',
            'created_by' => $this->admin->id,
        ]);

        $material = LearningMaterial::create([
            'learning_category_id' => $category->id,
            'title' => 'Modul PLC Basic',
            'type' => 'video',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);

        $postTestQuiz = Quiz::create([
            'title' => 'Post-Test: Modul PLC Basic',
            'type' => 'post_test',
            'related_type' => 'learning_material',
            'related_id' => $material->id,
            'points_reward' => 100,
            'description' => 'Kerjakan post-test ini setelah materi tuntas.',
        ]);

        $q = QuizQuestion::create([
            'quiz_id' => $postTestQuiz->id,
            'question_text' => 'Apa kepanjangan PLC?',
            'order_index' => 1,
        ]);

        QuizOption::create([
            'quiz_question_id' => $q->id,
            'option_text' => 'Programmable Logic Controller',
            'is_correct' => true,
        ]);

        // 1. Progres materi baru 50%
        $this->actingAs($this->employee)->patchJson("/api/learning-materials/{$material->id}/progress", [
            'progress_percent' => 50,
        ]);

        // Request detail materi -> post_test harus NULL (terkunci)
        $res50 = $this->actingAs($this->employee)->getJson("/api/learning-materials/{$material->id}");
        $res50->assertOk();
        $this->assertNull($res50->json('data.post_test'));

        // 2. Progres diupdate menjadi 100%
        $this->actingAs($this->employee)->patchJson("/api/learning-materials/{$material->id}/progress", [
            'progress_percent' => 100,
        ]);

        // Request detail materi -> post_test sekarang MUNCUL
        $res100 = $this->actingAs($this->employee)->getJson("/api/learning-materials/{$material->id}");
        $res100->assertOk();
        $this->assertNotNull($res100->json('data.post_test'));
        $this->assertSame($postTestQuiz->id, $res100->json('data.post_test.id'));
        $this->assertSame('Post-Test: Modul PLC Basic', $res100->json('data.post_test.title'));
        $this->assertSame(100, $res100->json('data.post_test.points_reward'));
    }
}
