<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LearningCategoryController extends Controller
{
    /**
     * Display a listing of learning categories.
     * GET /api/learning-categories
     */
    public function index(Request $request): JsonResponse
    {
        $categories = LearningCategory::withCount([
            'materials' => function ($q) use ($request) {
                if (! $request->user()->hasAnyRole(['admin', 'quality', 'supervisor'])) {
                    $q->where('status', 'published');
                }
            },
        ])->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created learning category.
     * POST /api/learning-categories
     * Sesuai PRD 2.2: Hanya role Quality dan Admin yang boleh membuat kategori.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', LearningCategory::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:learning_categories,name'],
        ]);

        $category = LearningCategory::create([
            'name' => $validated['name'],
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori pembelajaran berhasil dibuat.',
            'data' => $category,
        ], 201);
    }

    /**
     * Display materials under the specified learning category with user progress.
     * GET /api/learning-categories/{id}/materials
     */
    public function materials(Request $request, string|int $id): JsonResponse
    {
        $category = LearningCategory::findOrFail($id);
        $user = $request->user();

        $query = $category->materials()
            ->with(['progresses' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);

        if (! $user->hasAnyRole(['admin', 'quality', 'supervisor'])) {
            $query->published();
        }

        $materials = $query->latest()->get()->map(function (LearningMaterial $material) {
            $progress = $material->progresses->first();

            return [
                'id' => $material->id,
                'learning_category_id' => $material->learning_category_id,
                'title' => $material->title,
                'type' => $material->type,
                'content_url' => $material->content_url,
                'description' => $material->description,
                'status' => $material->status,
                'created_by' => $material->created_by,
                'created_at' => $material->created_at,
                'progress_percent' => $progress?->progress_percent ?? 0,
                'completed_at' => $progress?->completed_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'materials' => $materials,
            ],
        ]);
    }
}
