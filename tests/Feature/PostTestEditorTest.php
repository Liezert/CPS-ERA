<?php

namespace Tests\Feature;

use App\Livewire\Learning\PostTestEditor;
use App\Models\Division;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserLearningProgress;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostTestEditorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private LearningMaterial $material;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['division_id' => Division::first()->id]);
        $this->admin->assignRole('admin');

        $this->material = LearningMaterial::create([
            'learning_category_id' => LearningCategory::create(['name' => 'K3', 'created_by' => $this->admin->id])->id,
            'title' => 'Cara Membersihkan Nozzle',
            'type' => 'video',
            'status' => 'published',
            'created_by' => $this->admin->id,
        ]);
    }

    private function postTest(): ?Quiz
    {
        return $this->material->postTest()->with('questions.options')->first();
    }

    public function test_admin_builds_a_post_test_with_question_cards(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PostTestEditor::class, ['material' => $this->material])
            ->assertSet('title', 'Post-Test: Cara Membersihkan Nozzle')
            ->set('questions.0.text', 'APD wajib saat membersihkan nozzle?')
            ->set('questions.0.options.0.text', 'Sarung tangan tahan panas')
            ->set('questions.0.options.1.text', 'Tidak perlu APD')
            ->call('toggleCorrect', 0, 0)
            ->call('addQuestion')
            ->call('setMultiple', 1, true)
            ->set('questions.1.text', 'Langkah sebelum membersihkan?')
            ->set('questions.1.options.0.text', 'Matikan mesin')
            ->set('questions.1.options.1.text', 'Pasang lockout-tagout')
            ->call('addOption', 1)
            ->set('questions.1.options.2.text', 'Langsung buka nozzle')
            ->call('toggleCorrect', 1, 0)
            ->call('toggleCorrect', 1, 1)
            ->call('save')
            ->assertHasNoErrors();

        $quiz = $this->postTest();
        $this->assertSame('post_test', $quiz->type);
        $this->assertSame(['APD wajib saat membersihkan nozzle?', 'Langkah sebelum membersihkan?'], $quiz->questions->pluck('question_text')->all());
        $this->assertSame([false, true], $quiz->questions->pluck('allow_multiple_answers')->all());
        $this->assertSame(['Matikan mesin', 'Pasang lockout-tagout'], $quiz->questions[1]->options->where('is_correct', true)->pluck('option_text')->values()->all());
    }

    public function test_single_choice_keeps_one_answer_and_every_question_needs_a_key(): void
    {
        $editor = Livewire::actingAs($this->admin)
            ->test(PostTestEditor::class, ['material' => $this->material])
            ->set('questions.0.text', 'Soal tanpa kunci')
            ->set('questions.0.options.0.text', 'A')
            ->set('questions.0.options.1.text', 'B')
            ->call('save')
            ->assertHasErrors('questions.0.correct');
        $this->assertNull($this->postTest());

        // Pilihan ganda: menandai opsi lain memindahkan kunci, bukan menambah.
        $editor->call('toggleCorrect', 0, 0)->call('toggleCorrect', 0, 1)
            ->assertSet('questions.0.options.0.correct', false)
            ->assertSet('questions.0.options.1.correct', true);

        // Kotak centang -> pilihan ganda: hanya kunci pertama yang dipertahankan.
        $editor->call('setMultiple', 0, true)->call('toggleCorrect', 0, 0)->call('setMultiple', 0, false)
            ->assertSet('questions.0.options.0.correct', true)
            ->assertSet('questions.0.options.1.correct', false);
    }

    public function test_editing_reorders_questions_without_touching_attempt_history(): void
    {
        $quiz = Quiz::create(['title' => 'Post-Test Lama', 'type' => 'post_test', 'related_type' => 'learning_material', 'related_id' => $this->material->id, 'points_reward' => 20]);
        foreach (['Soal Satu', 'Soal Dua'] as $i => $text) {
            $question = $quiz->questions()->create(['question_text' => $text, 'order_index' => $i + 1, 'allow_multiple_answers' => false]);
            $question->options()->createMany([['option_text' => 'Benar', 'is_correct' => true], ['option_text' => 'Salah', 'is_correct' => false]]);
        }
        QuizAttempt::create(['quiz_id' => $quiz->id, 'user_id' => $this->admin->id, 'score' => 100, 'passed' => true, 'points_earned' => 20, 'attempted_at' => now()]);

        Livewire::actingAs($this->admin)
            ->test(PostTestEditor::class, ['material' => $this->material])
            ->assertSet('questions.1.text', 'Soal Dua')
            ->call('moveQuestion', 1, -1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['Soal Dua', 'Soal Satu'], $this->postTest()->questions->pluck('question_text')->all());
        $this->assertSame($quiz->id, $this->postTest()->id);
        $this->assertSame(1, QuizAttempt::where('quiz_id', $quiz->id)->count());
    }

    public function test_question_formatting_is_sanitized_and_rendered_on_the_post_test_page(): void
    {
        $editor = Livewire::actingAs($this->admin)
            ->test(PostTestEditor::class, ['material' => $this->material])
            ->set('questions.0.text', '<b>Tebal</b> <i>miring</i> <u>garis</u> <a href="https://cps.test/sop">SOP</a> <a href="javascript:alert(1)">jahat</a><script>alert(1)</script><img src=x onerror=alert(1)>')
            ->set('questions.0.options.0.text', 'A')
            ->set('questions.0.options.1.text', 'B')
            ->call('toggleCorrect', 0, 0);

        // Hapus lalu urungkan: soal kembali utuh di posisinya.
        $editor->call('addQuestion')->call('removeQuestion', 0)
            ->assertCount('questions', 1)
            ->call('undoRemove')
            ->assertCount('questions', 2)
            ->call('removeQuestion', 1)
            ->call('save')
            ->assertHasNoErrors();

        $stored = $this->postTest()->questions->first()->question_text;
        $this->assertStringContainsString('<b>Tebal</b> <i>miring</i> <u>garis</u>', $stored);
        $this->assertStringContainsString('<a href="https://cps.test/sop" target="_blank" rel="noopener noreferrer">SOP</a>', $stored);
        $this->assertStringNotContainsString('javascript:', $stored);
        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('onerror', $stored);

        // Halaman pengerjaan: format tampil, label jenis soal & catatan kunci jawaban tidak lagi ditampilkan.
        $employee = User::factory()->create(['division_id' => Division::first()->id]);
        $employee->assignRole('employee');
        UserLearningProgress::create(['user_id' => $employee->id, 'learning_material_id' => $this->material->id, 'progress_percent' => 100, 'completed_at' => now()]);

        $this->actingAs($employee)->get(route('learning.post-test', $this->material))
            ->assertOk()
            ->assertSee('<b>Tebal</b>', false)
            ->assertDontSee('Pilihan Tunggal')
            ->assertDontSee('Pilih Semua Jawaban Benar')
            ->assertDontSee('Kunci jawaban disembunyikan');
    }

    public function test_blank_formatted_question_is_rejected(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PostTestEditor::class, ['material' => $this->material])
            ->set('questions.0.text', '<br><b> </b>')
            ->set('questions.0.options.0.text', 'A')
            ->set('questions.0.options.1.text', 'B')
            ->call('toggleCorrect', 0, 0)
            ->call('save')
            ->assertHasErrors('questions.0.text');
    }

    public function test_employees_cannot_open_the_editor(): void
    {
        $employee = User::factory()->create(['division_id' => Division::first()->id]);
        $employee->assignRole('employee');

        $this->actingAs($employee)->get(route('learning.post-test.edit', $this->material))->assertForbidden();
        $this->actingAs($this->admin)->get(route('learning.post-test.edit', $this->material))->assertOk()->assertSee('Tambah pertanyaan');
    }
}
