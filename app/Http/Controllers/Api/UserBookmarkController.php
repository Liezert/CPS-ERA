<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeDocument;
use App\Models\UserBookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserBookmarkController extends Controller
{
    /**
     * Display a listing of bookmarked knowledge documents for the authenticated user.
     * Sesuai PRD 3.2: GET /api/me/bookmarks
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);

        $bookmarkedDocs = $request->user()
            ->bookmarkedDocuments()
            ->with(['division', 'creator:id,name,employee_id', 'sourceBa:id,nomor_ba'])
            ->latest('user_bookmarks.created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $bookmarkedDocs,
        ]);
    }

    /**
     * Add a knowledge document to user's bookmarks.
     * Sesuai PRD 3.2: POST /api/knowledge-documents/{id}/bookmark
     * Menolak duplikasi bookmark per user.
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $document = KnowledgeDocument::findOrFail($id);
        $user = $request->user();

        $alreadyBookmarked = UserBookmark::where('user_id', $user->id)
            ->where('knowledge_document_id', $document->id)
            ->exists();

        if ($alreadyBookmarked) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen ini sudah tersimpan di bookmark Anda.',
            ], 422);
        }

        $bookmark = UserBookmark::create([
            'user_id' => $user->id,
            'knowledge_document_id' => $document->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil ditambahkan ke bookmark.',
            'data' => $bookmark,
        ], 201);
    }

    /**
     * Remove a knowledge document from user's bookmarks.
     * Sesuai PRD 3.2: DELETE /api/knowledge-documents/{id}/bookmark
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $deleted = UserBookmark::where('user_id', $user->id)
            ->where('knowledge_document_id', $id)
            ->delete();

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Bookmark tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil dihapus dari bookmark.',
        ]);
    }
}
