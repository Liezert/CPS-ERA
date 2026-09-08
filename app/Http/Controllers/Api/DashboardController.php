<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeDocument;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserLearningProgress;
use App\Services\LevelCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tampilkan ringkasan dashboard untuk user yang sedang login.
     * Mengagregasikan data dari modul Knowledge, Learning, Quiz/Misi, dan Ledger Poin.
     *
     * GET /api/dashboard/summary
     */
    public function summary(Request $request, LevelCalculator $levelCalculator): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // 1. Jumlah knowledge_documents yang dibuat oleh user
        $knowledgeDocumentsCount = KnowledgeDocument::where('created_by', $user->id)->count();

        // 2. Jumlah lesson_learned yang terkait user (sebagai pembuat atau pemilik BA sumber)
        $lessonLearnedCount = KnowledgeDocument::where('type', 'lesson_learned')
            ->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereHas('sourceBa', fn ($ba) => $ba->where('created_by', $user->id));
            })
            ->count();

        // 3. Rata-rata progress belajar user (%)
        $averageLearningProgress = (float) round(
            (float) (UserLearningProgress::where('user_id', $user->id)->avg('progress_percent') ?? 0),
            1
        );

        // 4. Level & Poin user
        $totalPoints = (int) $user->total_points;
        $level = (int) $user->level;
        $pointsToNextLevel = $levelCalculator->pointsToNextLevel($totalPoints);
        $nextLevelThreshold = $levelCalculator->nextLevelThreshold($totalPoints);
        $levelProgressPercent = $levelCalculator->levelProgressPercent($totalPoints);

        // 5. Materi learning yang belum selesai (maksimal 3)
        // Prioritas 1: materi yang sedang berlangsung (progress < 100%)
        $inProgress = LearningMaterial::published()
            ->whereHas('progresses', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('progress_percent', '<', 100);
            })
            ->with(['category:id,name', 'progresses' => fn ($q) => $q->where('user_id', $user->id)])
            ->take(3)
            ->get();

        $remainingSlots = 3 - $inProgress->count();
        $notStarted = collect();
        if ($remainingSlots > 0) {
            $notStarted = LearningMaterial::published()
                ->whereDoesntHave('progresses', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->with(['category:id,name'])
                ->take($remainingSlots)
                ->get();
        }

        $unfinishedMaterials = $inProgress->concat($notStarted)->map(function (LearningMaterial $material) {
            return [
                'id' => $material->id,
                'title' => $material->title,
                'type' => $material->type,
                'category' => $material->category ? [
                    'id' => $material->category->id,
                    'name' => $material->category->name,
                ] : null,
                'progress_percent' => (int) ($material->progresses->first()?->progress_percent ?? 0),
            ];
        })->values();

        // 6. Misi yang belum dikerjakan / belum lulus (maksimal 3)
        $unfinishedMissions = Quiz::whereIn('type', ['mission_case_study', 'mission_quiz'])
            ->whereDoesntHave('attempts', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('passed', true);
            })
            ->take(3)
            ->get(['id', 'title', 'type', 'points_reward', 'description']);

        // 7. 3 knowledge_documents terbaru (published)
        $latestKnowledgeDocuments = KnowledgeDocument::published()
            ->with(['division:id,name', 'creator:id,name'])
            ->latest()
            ->take(3)
            ->get()
            ->map(function (KnowledgeDocument $doc) {
                return [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'type' => $doc->type,
                    'division' => $doc->division ? [
                        'id' => $doc->division->id,
                        'name' => $doc->division->name,
                    ] : null,
                    'creator' => $doc->creator ? [
                        'id' => $doc->creator->id,
                        'name' => $doc->creator->name,
                    ] : null,
                    'created_at' => $doc->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'knowledge_documents_count' => $knowledgeDocumentsCount,
                'lesson_learned_count' => $lessonLearnedCount,
                'average_learning_progress' => $averageLearningProgress,

                // CATATAN PRD 5.3 poin 2: Rumus KPI belum difinalkan, eksplisit null
                'kpi_contribution_percent' => null,
                'kpi_contribution_status' => 'not_implemented',

                'level' => $level,
                'total_points' => $totalPoints,
                'points_to_next_level' => $pointsToNextLevel,
                'next_level_threshold' => $nextLevelThreshold,
                'level_progress_percent' => $levelProgressPercent,

                'unfinished_learning_materials' => $unfinishedMaterials,
                'unfinished_missions' => $unfinishedMissions,
                'latest_knowledge_documents' => $latestKnowledgeDocuments,
            ],
        ]);
    }
}
