<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KnowledgeDocumentController extends Controller
{
    /**
     * Display a listing of knowledge documents with search and filtering.
     * Sesuai PRD 3.2: q, category_id/division_id, type.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', KnowledgeDocument::class);

        $user = $request->user();

        $query = KnowledgeDocument::query()
            ->with(['division', 'creator:id,name,employee_id', 'sourceBa:id,nomor_ba'])
            ->withExists(['bookmarks as is_bookmarked' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);

        // Non-admin/supervisor only see published docs by default
        if (! $user->hasAnyRole(['admin', 'supervisor', 'quality'])) {
            $query->published();
        } elseif ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $query->filter($request->only(['q', 'category_id', 'division_id', 'type']));

        $perPage = (int) $request->query('per_page', 15);
        $documents = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }

    /**
     * Display the specified knowledge document.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $document = KnowledgeDocument::with(['division', 'creator:id,name,employee_id', 'sourceBa:id,nomor_ba,status'])
            ->findOrFail($id);

        Gate::authorize('view', $document);

        $documentData = $document->toArray();
        $documentData['is_bookmarked'] = $document->isBookmarkedBy($request->user());

        return response()->json([
            'success' => true,
            'data' => $documentData,
        ]);
    }

    /**
     * Store a newly created knowledge document.
     * Validasi: type=lesson_learned HARUS ditolak untuk create manual.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', KnowledgeDocument::class);

        // Explicit validation to reject lesson_learned directly
        if ($request->input('type') === 'lesson_learned') {
            throw ValidationException::withMessages([
                'type' => ['Tipe lesson_learned hanya dapat dibuat otomatis oleh sistem melalui review BA.'],
            ]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'division_id' => ['required_without:category_id', 'nullable', 'exists:divisions,id'],
            'category_id' => ['required_without:division_id', 'nullable', 'exists:divisions,id'],
            'type' => [
                'required',
                'string',
                Rule::in(['dokumen', 'video', 'presentasi', 'sop', 'link']),
            ],
            'description' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:20480'],
            'file_url' => ['nullable', 'string', 'max:255'],
            'external_link' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'published'])],
        ]);

        $divisionId = $validated['division_id'] ?? $validated['category_id'];
        $fileUrl = $validated['file_url'] ?? null;

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('knowledge-documents/files', 'public');
            $fileUrl = Storage::url($path);
        }

        $document = KnowledgeDocument::create([
            'title' => $validated['title'],
            'division_id' => $divisionId,
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'file_url' => $fileUrl,
            'external_link' => $validated['external_link'] ?? null,
            'created_by' => $request->user()->id,
            'status' => $validated['status'] ?? 'published',
        ]);

        $document->load(['division', 'creator:id,name,employee_id']);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen pengetahuan berhasil dibuat.',
            'data' => $document,
        ], 201);
    }

    /**
     * Update the specified knowledge document.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $document = KnowledgeDocument::findOrFail($id);

        Gate::authorize('update', $document);

        // Cannot change type to lesson_learned
        if ($request->input('type') === 'lesson_learned' && ! $document->isLessonLearned()) {
            throw ValidationException::withMessages([
                'type' => ['Tipe materi tidak dapat diubah menjadi lesson_learned.'],
            ]);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'division_id' => ['sometimes', 'nullable', 'exists:divisions,id'],
            'category_id' => ['sometimes', 'nullable', 'exists:divisions,id'],
            'type' => [
                'sometimes',
                'required',
                'string',
                Rule::in($document->isLessonLearned() ? ['lesson_learned'] : ['dokumen', 'video', 'presentasi', 'sop', 'link']),
            ],
            'description' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:20480'],
            'file_url' => ['nullable', 'string', 'max:255'],
            'external_link' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'published'])],
        ]);

        if (isset($validated['category_id']) && ! isset($validated['division_id'])) {
            $validated['division_id'] = $validated['category_id'];
        }
        unset($validated['category_id']);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('knowledge-documents/files', 'public');
            $validated['file_url'] = Storage::url($path);
        }

        $document->update($validated);
        $document->load(['division', 'creator:id,name,employee_id', 'sourceBa:id,nomor_ba']);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen pengetahuan berhasil diperbarui.',
            'data' => $document,
        ]);
    }

    /**
     * Remove the specified knowledge document.
     * Dibatasi oleh KnowledgeDocumentPolicy (PRD 2.2).
     */
    public function destroy(string $id): JsonResponse
    {
        $document = KnowledgeDocument::findOrFail($id);

        Gate::authorize('delete', $document);

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dokumen pengetahuan berhasil dihapus.',
        ]);
    }
}
