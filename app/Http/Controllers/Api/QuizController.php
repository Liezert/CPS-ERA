<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\PointTransaction;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\KpiContributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class QuizController extends Controller
{
    /**
     * Display a listing of quizzes with filter by type.
     * GET /api/quizzes?type=mission_case_study,mission_quiz
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Quiz::class);

        $query = Quiz::withCount('questions');

        if ($request->filled('type')) {
            $types = explode(',', (string) $request->query('type'));
            $query->whereIn('type', $types);
        }

        $quizzes = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $quizzes,
        ]);
    }

    /**
     * Display the specified quiz with questions & options.
     * KEAMANAN: Field is_correct WAJIB TIDAK DITAMPILKAN ke response!
     * GET /api/quizzes/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $quiz = Quiz::with(['questions.options'])->findOrFail($id);

        Gate::authorize('view', $quiz);

        $questionsData = $quiz->questions->map(function (QuizQuestion $question) {
            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'order_index' => $question->order_index,
                'allow_multiple_answers' => (bool) $question->allow_multiple_answers,
                'options' => $question->options->map(function (QuizOption $option) {
                    return [
                        'id' => $option->id,
                        'option_text' => $option->option_text,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'type' => $quiz->type,
                'related_type' => $quiz->related_type,
                'related_id' => $quiz->related_id,
                'points_reward' => $quiz->points_reward,
                'description' => $quiz->description,
                'questions_count' => $quiz->questions->count(),
                'questions' => $questionsData,
            ],
        ]);
    }

    /**
     * Submit an attempt for the quiz.
     * Evaluasi score & passed dihitung murni di backend (tidak percaya input client).
     * POST /api/quizzes/{id}/attempt
     */
    public function attempt(Request $request, string $id): JsonResponse
    {
        $quiz = Quiz::with('questions.options')->findOrFail($id);

        Gate::authorize('view', $quiz);

        /** @var User $user */
        $user = $request->user();

        // Ambil mapping jawaban
        // Mendukung:
        // 1. { "selected_option_id": "uuid" } (untuk kuis 1 pertanyaan / PRD 3.4)
        // 2. { "answers": [ { "question_id": "uuid", "selected_option_id": "uuid" } ] }
        // 3. { "answers": { "question_id": "selected_option_id" } }
        $userAnswers = [];

        if ($request->filled('selected_option_id')) {
            $firstQuestion = $quiz->questions->first();
            if ($firstQuestion) {
                $userAnswers[$firstQuestion->id] = (string) $request->input('selected_option_id');
            }
        } elseif ($request->has('answers')) {
            $rawAnswers = $request->input('answers');
            if (is_array($rawAnswers)) {
                foreach ($rawAnswers as $key => $val) {
                    if (is_array($val) && isset($val['question_id'])) {
                        $userAnswers[$val['question_id']] = $val['selected_option_ids'] ?? ($val['selected_option_id'] ?? []);
                    } elseif (is_string($key)) {
                        $userAnswers[$key] = $val;
                    }
                }
            }
        }

        // Kalkulasi server-side murni
        $totalQuestions = $quiz->questions->count();
        $correctCount = 0;

        foreach ($quiz->questions as $question) {
            $userAnswer = $userAnswers[$question->id] ?? null;

            if ($question->allow_multiple_answers) {
                $selectedIds = is_array($userAnswer) ? $userAnswer : ($userAnswer ? [$userAnswer] : []);
                $selectedIds = array_map('strval', $selectedIds);
                sort($selectedIds);

                $correctIds = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (string) $id)->all();
                sort($correctIds);

                if (! empty($correctIds) && array_values($selectedIds) === array_values($correctIds)) {
                    $correctCount++;
                }
            } else {
                $selectedOptionId = is_array($userAnswer) ? ($userAnswer[0] ?? null) : $userAnswer;
                if ($selectedOptionId) {
                    $chosenOption = $question->options->firstWhere('id', $selectedOptionId);
                    if ($chosenOption && $chosenOption->is_correct) {
                        $correctCount++;
                    }
                }
            }
        }

        $score = $totalQuestions > 0 ? (int) round(($correctCount / $totalQuestions) * 100) : 0;
        $isPostTest = $quiz->type === 'post_test';
        $passed = $isPostTest ? ($score === 100) : ($totalQuestions > 0 && ($score >= 70 || $correctCount === $totalQuestions));
        $pointsEarned = (! $isPostTest && $passed) ? (int) $quiz->points_reward : 0;

        $attempt = DB::transaction(function () use ($quiz, $user, $score, $passed, $pointsEarned, $isPostTest) {
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'score' => $score,
                'passed' => $passed,
                'points_earned' => $pointsEarned,
                'attempted_at' => now(),
            ]);

            // Jika misi game dan lulus, catat transaksi poin XP
            if (! $isPostTest && $passed && $pointsEarned > 0) {
                PointTransaction::create([
                    'user_id' => $user->id,
                    'ledger_type' => PointTransaction::LEDGER_XP,
                    'points' => $pointsEarned,
                    'source_type' => 'mission_completed',
                    'source_id' => $quiz->id,
                    'description' => "Menyelesaikan quiz/misi: {$quiz->title}",
                    'created_at' => now(),
                ]);
            }

            // Jika post-test lulus 100%, picu KPI Contribution
            if ($isPostTest && $passed && $quiz->related_type === 'learning_material' && $quiz->related_id) {
                $material = LearningMaterial::find($quiz->related_id);
                if ($material) {
                    app(KpiContributionService::class)->recordMaterialCompletion($user, $material, $score, $attempt);
                }
            }

            return $attempt;
        });

        return response()->json([
            'success' => true,
            'message' => $passed
                ? 'Selamat, Anda berhasil menyelesaikan kuis!'
                : 'Jawaban belum tepat, silakan pelajari kembali materinya.',
            'data' => [
                'attempt_id' => $attempt->id,
                'score' => $score,
                'passed' => $passed,
                'points_earned' => $pointsEarned,
                'xp' => (int) $user->fresh()->xp,
                'total_points' => (int) $user->fresh()->xp,
            ],
        ]);
    }
}
