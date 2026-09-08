<?php

namespace App\Livewire\Mission;

use App\Models\PointTransaction;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Misi & Game - CPS ERA')]
class Show extends Component
{
    public Quiz $quiz;

    /**
     * State jawaban pengguna: question_id => option_id
     *
     * @var array<string, string>
     */
    public array $userAnswers = [];

    /**
     * Evaluasi instan jawaban: question_id => ['selected' => string, 'is_correct' => bool, 'correct_option_id' => string]
     *
     * @var array<string, array{selected: string, is_correct: bool, correct_option_id: ?string}>
     */
    public array $evaluatedAnswers = [];

    public bool $isSubmitted = false;

    public int $score = 0;

    public bool $passed = false;

    public int $pointsEarned = 0;

    public bool $alreadyRewarded = false;

    public ?QuizAttempt $latestAttempt = null;

    /**
     * Inisialisasi komponen detail misi (Case Study / Quiz).
     */
    public function mount(Quiz|string $quiz): void
    {
        if (is_string($quiz)) {
            $this->quiz = Quiz::missions()
                ->with(['questions.options'])
                ->where('id', $quiz)
                ->firstOrFail();
        } else {
            $this->quiz = $quiz->loadMissing(['questions.options']);
        }

        $userId = Auth::id();
        if ($userId) {
            // Ambil attempt terakhir jika sudah pernah mengerjakan
            $this->latestAttempt = QuizAttempt::where('quiz_id', $this->quiz->id)
                ->where('user_id', $userId)
                ->latest('attempted_at')
                ->first();

            // Cek apakah user sudah pernah mendapat reward poin untuk misi ini
            $this->alreadyRewarded = PointTransaction::where('user_id', $userId)
                ->where('source_type', 'mission_completed')
                ->where('source_id', $this->quiz->id)
                ->exists();
        }
    }

    /**
     * DoD #1: Feedback instan tanpa reload.
     * Pengguna memilih opsi pilihan ganda dan sistem langsung mengevaluasi
     * kebenaran jawaban secara reaktif di sisi Livewire tanpa reload halaman.
     */
    public function selectOption(string $questionId, string $optionId): void
    {
        // Cari opsi yang dipilih beserta opsi yang benar
        $selectedOption = QuizOption::where('quiz_question_id', $questionId)
            ->where('id', $optionId)
            ->first();

        $correctOption = QuizOption::where('quiz_question_id', $questionId)
            ->where('is_correct', true)
            ->first();

        $isCorrect = (bool) ($selectedOption?->is_correct ?? false);

        $this->userAnswers[$questionId] = $optionId;

        // Simpan hasil evaluasi instan untuk rendering visual (teks/border/ikon)
        $this->evaluatedAnswers[$questionId] = [
            'selected' => $optionId,
            'is_correct' => $isCorrect,
            'correct_option_id' => $correctOption?->id,
        ];
    }

    /**
     * Selesaikan misi, hitung skor total, dan catat poin.
     * DoD #2: Poin tercatat lewat point_transactions (source_type = mission_completed).
     */
    public function submitQuiz(): void
    {
        $userId = Auth::id();
        if (! $userId) {
            return;
        }

        $questions = $this->quiz->questions;
        $totalQuestions = $questions->count();

        if ($totalQuestions === 0) {
            return;
        }

        // Hitung total jawaban yang benar
        $correctCount = 0;
        foreach ($questions as $question) {
            $evaluated = $this->evaluatedAnswers[$question->id] ?? null;
            if ($evaluated && $evaluated['is_correct']) {
                $correctCount++;
            }
        }

        $this->score = (int) round(($correctCount / $totalQuestions) * 100);
        // Passing grade: minimum 70%
        $this->passed = ($this->score >= 70);

        // Cek ledger point_transactions: apakah sudah pernah diberi reward untuk misi ini?
        $hasPointTransaction = PointTransaction::where('user_id', $userId)
            ->where('source_type', 'mission_completed')
            ->where('source_id', $this->quiz->id)
            ->exists();

        $this->alreadyRewarded = $hasPointTransaction;

        // Tentukan perolehan poin: hanya jika lulus dan belum pernah klaim
        if ($this->passed && ! $hasPointTransaction) {
            $this->pointsEarned = (int) $this->quiz->points_reward;

            // Catat poin resmi ke ledger point_transactions (DoD #2)
            PointTransaction::create([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'points' => $this->pointsEarned,
                'source_type' => 'mission_completed',
                'source_id' => $this->quiz->id,
                'description' => "Menyelesaikan Misi: {$this->quiz->title}",
                'created_at' => now(),
            ]);

            $this->alreadyRewarded = true;
        } else {
            $this->pointsEarned = 0;
        }

        // Catat riwayat percobaan di quiz_attempts
        $this->latestAttempt = QuizAttempt::create([
            'id' => (string) Str::uuid(),
            'quiz_id' => $this->quiz->id,
            'user_id' => $userId,
            'score' => $this->score,
            'passed' => $this->passed,
            'points_earned' => $this->pointsEarned,
            'attempted_at' => now(),
        ]);

        $this->isSubmitted = true;
    }

    /**
     * DoD #3: Retry policy ditandai TODO, bukan diasumsikan.
     *
     * TODO: Menunggu keputusan PRD §5.3 (Poin 4: Kebijakan retry kuis/misi).
     * Saat ini kebijakan retry belum disahkan oleh manajemen:
     * - Apakah pengguna diperbolehkan mengulang tanpa batas?
     * - Apakah dibatasi maksimal N kali percobaan per hari/minggu?
     * - Apakah ada jeda waktu (cooldown) antar percobaan?
     * - Apakah skor yang dicatat adalah percobaan pertama, tertinggi, atau terakhir?
     *
     * Default interim MVP (Data Contract §3):
     * Pengguna diperbolehkan mengulang misi untuk sarana belajar mandiri,
     * namun pencatatan poin ke ledger point_transactions HANYA diberikan 1x seumur hidup
     * per quiz_id (tidak menambah poin ganda jika sudah pernah lulus).
     */
    public function resetQuiz(): void
    {
        $this->userAnswers = [];
        $this->evaluatedAnswers = [];
        $this->isSubmitted = false;
        $this->score = 0;
        $this->passed = false;
        $this->pointsEarned = 0;
    }

    public function render(): View
    {
        return view('livewire.mission.show', [
            'totalQuestions' => $this->quiz->questions->count(),
            'answeredCount' => count($this->userAnswers),
        ]);
    }
}
