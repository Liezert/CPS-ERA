<?php

namespace App\Livewire\Learning;

use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\User;
use App\Models\UserLearningProgress;
use App\Services\KpiContributionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Evaluasi Post-Test - CPS ERA')]
class PostTest extends Component
{
    public LearningMaterial $material;

    public ?Quiz $quiz = null;

    /**
     * State jawaban pengguna:
     * - Single-select: question_id => option_id
     * - Multi-select: question_id => [option_id, ...]
     *
     * @var array<string, string|array<int, string>>
     */
    public array $userAnswers = [];

    public bool $isSubmitted = false;

    public int $score = 0;

    public bool $passed = false;

    public ?QuizAttempt $latestAttempt = null;

    public ?UserLearningProgress $progress = null;

    /**
     * Inisialisasi evaluasi Post-Test.
     * Menerapkan gating ketat: pengguna harus telah menyelesaikan materi 100%.
     */
    public function mount(LearningMaterial|string $material): void
    {
        if (is_string($material)) {
            $this->material = LearningMaterial::with(['category', 'creator', 'postTest.questions.options'])
                ->where('id', $material)
                ->firstOrFail();
        } else {
            $this->material = $material->loadMissing(['category', 'creator', 'postTest.questions.options']);
        }

        // Materi belum terbit (draft/candidate) hanya untuk pengelolanya.
        abort_unless($this->material->status === 'published' || (Auth::user()?->can('update', $this->material) ?? false), 404);

        $userId = Auth::id();
        if (! $userId) {
            $this->redirect(route('login'));

            return;
        }

        // Gating Check: Pastikan materi telah selesai 100%
        $this->progress = UserLearningProgress::where('user_id', $userId)
            ->where('learning_material_id', $this->material->id)
            ->first();

        if (! $this->progress || (int) $this->progress->progress_percent < 100) {
            session()->flash('error', 'Selesaikan materi pembelajaran (progress 100%) terlebih dahulu untuk mengakses evaluasi Post-Test.');
            $this->redirect(route('learning.show', $this->material), navigate: true);

            return;
        }

        $this->quiz = $this->material->postTest;

        if (! $this->quiz) {
            session()->flash('error', 'Materi pembelajaran ini tidak memiliki evaluasi Post-Test.');
            $this->redirect(route('learning.show', $this->material), navigate: true);

            return;
        }

        // KEAMANAN PRD v2.0 §3.3: Sembunyikan field is_correct agar tidak bocor di payload Livewire
        if ($this->quiz->relationLoaded('questions')) {
            foreach ($this->quiz->questions as $question) {
                if ($question->relationLoaded('options')) {
                    foreach ($question->options as $option) {
                        $option->makeHidden(['is_correct']);
                    }
                }
            }
        }

        // Ambil riwayat percobaan terakhir
        $this->latestAttempt = QuizAttempt::where('quiz_id', $this->quiz->id)
            ->where('user_id', $userId)
            ->latest('attempted_at')
            ->first();
    }

    /**
     * Pilih opsi jawaban tunggal (single-select / radio).
     */
    public function selectOption(string $questionId, string $optionId): void
    {
        if ($this->isSubmitted) {
            return;
        }

        $this->userAnswers[$questionId] = $optionId;
    }

    /**
     * Toggle opsi jawaban ganda (multi-select / checkbox).
     */
    public function toggleOption(string $questionId, string $optionId): void
    {
        if ($this->isSubmitted) {
            return;
        }

        $current = $this->userAnswers[$questionId] ?? [];
        if (! is_array($current)) {
            $current = $current ? [$current] : [];
        }

        if (in_array($optionId, $current, true)) {
            $current = array_values(array_diff($current, [$optionId]));
        } else {
            $current[] = $optionId;
        }

        $this->userAnswers[$questionId] = $current;
    }

    /**
     * Kirim evaluasi Post-Test.
     * PRD v2.0 §3.3 & §3.5:
     * - Attempt tidak dibatasi (unlimited attempt).
     * - Syarat lulus & progress KPI: wajib 100% benar.
     * - Kunci jawaban & status benar/salah per soal selalu disembunyikan.
     * - Multi-select benar jika dan hanya jika user memilih persis semua opsi benar tanpa opsi salah.
     */
    public function submitPostTest(): void
    {
        $userId = Auth::id();
        if (! $userId || ! $this->quiz) {
            return;
        }

        $questions = $this->quiz->questions;
        $totalQuestions = $questions->count();

        if ($totalQuestions === 0) {
            return;
        }

        $correctCount = 0;

        foreach ($questions as $question) {
            $userAnswer = $this->userAnswers[$question->id] ?? null;

            if ($question->allow_multiple_answers) {
                // Multi-select question
                $selectedIds = is_array($userAnswer) ? $userAnswer : ($userAnswer ? [$userAnswer] : []);
                $selectedIds = array_map('strval', $selectedIds);
                sort($selectedIds);

                $correctIds = QuizOption::where('quiz_question_id', $question->id)
                    ->where('is_correct', true)
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->all();
                sort($correctIds);

                if (! empty($correctIds) && array_values($selectedIds) === array_values($correctIds)) {
                    $correctCount++;
                }
            } else {
                // Single-select question
                $selectedId = is_array($userAnswer) ? ($userAnswer[0] ?? null) : $userAnswer;
                if ($selectedId) {
                    $isCorrect = QuizOption::where('quiz_question_id', $question->id)
                        ->where('id', $selectedId)
                        ->where('is_correct', true)
                        ->exists();

                    if ($isCorrect) {
                        $correctCount++;
                    }
                }
            }
        }

        // Lulus hanya bila SEMUA soal benar; skor tampilan dibulatkan ke bawah sehingga
        // 100 tidak pernah muncul dari pembulatan (mis. 200/201 = 99,5 -> 99, bukan 100).
        $this->passed = $correctCount === $totalQuestions;
        $this->score = $this->passed ? 100 : (int) floor(($correctCount / $totalQuestions) * 100);

        // Catat riwayat percobaan di quiz_attempts tanpa batas attempt
        $this->latestAttempt = QuizAttempt::create([
            'quiz_id' => $this->quiz->id,
            'user_id' => $userId,
            'score' => $this->score,
            'passed' => $this->passed,
            'points_earned' => 0,
            'attempted_at' => now(),
        ]);

        // Jika lulus 100%, trigger KPI Contribution service (Jalur B). Attempt sudah tersimpan dan
        // progres KPI dihitung dari attempt, jadi kegagalan pencatatan poin tidak boleh menghapus hasil tes.
        if ($this->passed) {
            /** @var User $user */
            $user = Auth::user();

            try {
                app(KpiContributionService::class)->recordMaterialCompletion(
                    $user,
                    $this->material,
                    $this->score,
                    $this->latestAttempt
                );
            } catch (Throwable $e) {
                Log::error('Gagal mencatat KPI materi setelah post-test', [
                    'user_id' => $userId,
                    'material_id' => $this->material->id,
                    'attempt_id' => $this->latestAttempt->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->isSubmitted = true;
    }

    /**
     * Mengulang evaluasi Post-Test (unlimited attempt per PRD v2.0 §3.3).
     */
    public function resetPostTest(): void
    {
        $this->userAnswers = [];
        $this->isSubmitted = false;
        $this->score = 0;
        $this->passed = false;
    }

    public function render(): View
    {
        $totalQuestions = $this->quiz ? $this->quiz->questions->count() : 0;
        $answeredCount = 0;

        foreach ($this->userAnswers as $answer) {
            if (is_array($answer) ? count($answer) > 0 : ! empty($answer)) {
                $answeredCount++;
            }
        }

        return view('livewire.learning.post-test', [
            'totalQuestions' => $totalQuestions,
            'answeredCount' => $answeredCount,
        ]);
    }
}
