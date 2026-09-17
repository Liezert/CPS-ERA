<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeTopic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class KnowledgeTopicController extends Controller
{
    /**
     * Display a listing of knowledge topics.
     * Sesuai PRD v2.0 §3.2: Taksonomi Topik yang dikelola Admin/HRD.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', KnowledgeTopic::class);

        $query = KnowledgeTopic::query()
            ->withCount(['documents' => function ($q) {
                $q->published();
            }])
            ->with('creator:id,name,employee_id');

        if ($request->filled('q')) {
            $keyword = trim($request->query('q'));
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        $topics = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $topics,
        ]);
    }

    /**
     * Display the specified knowledge topic with its documents.
     */
    public function show(string $id): JsonResponse
    {
        $topic = KnowledgeTopic::withCount(['documents' => function ($q) {
            $q->published();
        }])
            ->with(['creator:id,name,employee_id', 'documents' => function ($q) {
                $q->published()->latest()->limit(20);
            }])
            ->findOrFail($id);

        Gate::authorize('view', $topic);

        return response()->json([
            'success' => true,
            'data' => $topic,
        ]);
    }

    /**
     * Store a newly created knowledge topic.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', KnowledgeTopic::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:knowledge_topics,name'],
            'description' => ['nullable', 'string'],
        ]);

        $topic = KnowledgeTopic::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $topic->load('creator:id,name,employee_id');

        return response()->json([
            'success' => true,
            'message' => 'Topik pengetahuan berhasil dibuat.',
            'data' => $topic,
        ], 201);
    }

    /**
     * Update the specified knowledge topic.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $topic = KnowledgeTopic::findOrFail($id);

        Gate::authorize('update', $topic);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150', 'unique:knowledge_topics,name,'.$topic->id],
            'description' => ['nullable', 'string'],
        ]);

        $topic->update($validated);
        $topic->load('creator:id,name,employee_id');

        return response()->json([
            'success' => true,
            'message' => 'Topik pengetahuan berhasil diperbarui.',
            'data' => $topic,
        ]);
    }

    /**
     * Remove the specified knowledge topic.
     */
    public function destroy(string $id): JsonResponse
    {
        $topic = KnowledgeTopic::findOrFail($id);

        Gate::authorize('delete', $topic);

        $topic->delete();

        return response()->json([
            'success' => true,
            'message' => 'Topik pengetahuan berhasil dihapus.',
        ]);
    }
}
