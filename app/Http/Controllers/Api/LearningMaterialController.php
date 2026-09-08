<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\UserLearningProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LearningMaterialController extends Controller
{
    /**
     * Store a newly created learning material.
     * POST /api/learning-materials
     * Employee boleh membuat, tapi status otomatis dipaksa 'draft'.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', LearningMaterial::class);

        $user = $request->user();

        $validated = $request->validate([
            'learning_category_id' => ['required', 'exists:learning_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => [
                'required',
                'string',
                Rule::in(['dokumen', 'video', 'presentasi', 'artikel', 'tutorial', 'link', 'file_pendukung']),
            ],
            'content_url' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:20480'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'published'])],
        ]);

        $contentUrl = $validated['content_url'] ?? null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('learning-materials/files', 'public');
            $contentUrl = Storage::url($path);
        }

        // Aturan PRD: Employee hanya boleh membuat materi dengan status draft
        $status = $user->hasRole('employee') ? 'draft' : ($validated['status'] ?? 'published');

        $material = LearningMaterial::create([
            'learning_category_id' => $validated['learning_category_id'],
            'title' => $validated['title'],
            'type' => $validated['type'],
            'content_url' => $contentUrl,
            'description' => $validated['description'] ?? null,
            'status' => $status,
            'created_by' => $user->id,
        ]);

        $material->load(['category', 'creator:id,name,employee_id']);

        return response()->json([
            'success' => true,
            'message' => 'Materi pembelajaran berhasil dibuat.',
            'data' => $material,
        ], 201);
    }

    /**
     * Display the specified learning material with user progress.
     * GET /api/learning-materials/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $material = LearningMaterial::with(['category', 'creator:id,name,employee_id'])->findOrFail($id);

        Gate::authorize('view', $material);

        $progress = $material->getProgressForUser($request->user());
        $isCompleted = ($progress?->progress_percent ?? 0) >= 100;

        $postTest = null;
        if ($isCompleted) {
            $postTestQuiz = Quiz::where('type', 'post_test')
                ->where('related_type', 'learning_material')
                ->where('related_id', $material->id)
                ->first();

            if ($postTestQuiz) {
                $postTest = [
                    'id' => $postTestQuiz->id,
                    'title' => $postTestQuiz->title,
                    'description' => $postTestQuiz->description,
                    'points_reward' => $postTestQuiz->points_reward,
                    'questions_count' => $postTestQuiz->questions()->count(),
                ];
            }
        }

        $data = $material->toArray();
        $data['progress_percent'] = $progress?->progress_percent ?? 0;
        $data['completed_at'] = $progress?->completed_at;
        $data['post_test'] = $postTest;

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Upsert user learning progress for the specified material.
     * PATCH /api/learning-materials/{id}/progress
     */
    public function updateProgress(Request $request, string $id): JsonResponse
    {
        $material = LearningMaterial::findOrFail($id);
        $user = $request->user();

        $validated = $request->validate([
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $percent = (int) $validated['progress_percent'];

        $progress = UserLearningProgress::firstOrNew([
            'user_id' => $user->id,
            'learning_material_id' => $material->id,
        ]);

        $progress->progress_percent = $percent;

        if ($percent >= 100 && $progress->completed_at === null) {
            $progress->completed_at = now();
        }

        $progress->save();

        return response()->json([
            'success' => true,
            'message' => 'Progres belajar berhasil diperbarui.',
            'data' => [
                'learning_material_id' => $material->id,
                'progress_percent' => $progress->progress_percent,
                'completed_at' => $progress->completed_at,
            ],
        ]);
    }

    /**
     * Display summary and listing of learning progress for the authenticated user.
     * GET /api/me/learning-progress
     */
    public function myProgress(Request $request): JsonResponse
    {
        $user = $request->user();

        $progresses = $user->learningProgresses()
            ->with(['material.category'])
            ->latest('updated_at')
            ->get();

        $totalStarted = $progresses->count();
        $totalCompleted = $progresses->where('progress_percent', '>=', 100)->count();
        $averageProgress = $totalStarted > 0 ? round($progresses->avg('progress_percent'), 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_materials_started' => $totalStarted,
                    'total_materials_completed' => $totalCompleted,
                    'average_progress_percent' => $averageProgress,
                ],
                'progresses' => $progresses,
            ],
        ]);
    }
}
