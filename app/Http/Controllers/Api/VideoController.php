<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BaIncident;
use App\Models\User;
use App\Models\UserVideoView;
use App\Models\Video;
use App\Services\VideoApprovalService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VideoController extends Controller
{
    /**
     * Tampilkan daftar video kontribusi dengan filter status, divisi, dan pencarian.
     *
     * GET /api/videos
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Video::with(['division:id,name', 'creator:id,name', 'baIncident:id,nomor_ba,title'])
            ->latest();

        // RBAC Scoping
        if (! $user->hasAnyRole(['admin', 'quality'])) {
            if ($user->hasRole('supervisor')) {
                // Supervisor dapat melihat video divisinya sendiri atau video yang sudah terbit publik
                $query->where(function ($q) use ($user) {
                    $q->where('division_id', $user->division_id)
                        ->orWhere('status', 'published')
                        ->orWhere('created_by', $user->id);
                });
            } else {
                // Employee melihat videonya sendiri atau video yang sudah terbit
                $query->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                        ->orWhere('status', 'published');
                });
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('creation_reason')) {
            $query->where('creation_reason', $request->query('creation_reason'));
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->query('division_id'));
        }

        if ($request->filled('q')) {
            $search = $request->query('q');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $videos = $query->paginate((int) $request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $videos,
        ]);
    }

    /**
     * Buat video kontribusi baru.
     * Validasi:
     * - mandatory_incident: wajib ada ba_incident_id (milik user/divisi sama, status != closed).
     * - voluntary_improvement: ba_incident_id wajib kosong, division_id wajib diisi.
     * - Status awal selalu 'pending_supervisor'.
     *
     * POST /api/videos
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video_url' => ['required', 'string', 'max:255'],
            'creation_reason' => ['required', Rule::in(['mandatory_incident', 'voluntary_improvement'])],
            'ba_incident_id' => ['nullable', 'string'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
        ]);

        $divisionId = $validated['division_id'] ?? null;
        $baIncidentId = $validated['ba_incident_id'] ?? null;

        // Jalur A: Wajib (akibat insiden)
        if ($validated['creation_reason'] === 'mandatory_incident') {
            if (empty($baIncidentId)) {
                throw ValidationException::withMessages([
                    'ba_incident_id' => ['Video jalur wajib (mandatory_incident) harus menyertakan ba_incident_id laporan insiden terkait.'],
                ]);
            }

            /** @var ?BaIncident $ba */
            $ba = BaIncident::find($baIncidentId);
            if (! $ba) {
                throw ValidationException::withMessages([
                    'ba_incident_id' => ['Laporan BA insiden tidak ditemukan.'],
                ]);
            }

            if ($ba->status === 'closed') {
                throw ValidationException::withMessages([
                    'ba_incident_id' => ['Laporan BA insiden sudah ditutup (closed), tidak dapat dikaitkan dengan video baru.'],
                ]);
            }

            $isOwnerOrSameDivision = ((string) $ba->created_by === (string) $user->id) ||
                ((int) $ba->division_id === (int) $user->division_id) ||
                $user->hasRole('admin');

            if (! $isOwnerOrSameDivision) {
                throw ValidationException::withMessages([
                    'ba_incident_id' => ['Anda hanya dapat membuat video wajib untuk BA dari divisi Anda sendiri.'],
                ]);
            }

            // Divisi video otomatis mengikuti divisi BA insiden jika tidak diberikan
            $divisionId = $divisionId ?: $ba->division_id;
        }

        // Jalur B: Sukarela (improvement)
        if ($validated['creation_reason'] === 'voluntary_improvement') {
            if (! empty($baIncidentId)) {
                throw ValidationException::withMessages([
                    'ba_incident_id' => ['Video jalur sukarela (voluntary_improvement) tidak boleh dikaitkan dengan laporan BA insiden.'],
                ]);
            }

            if (empty($divisionId)) {
                $divisionId = $user->division_id;
            }

            if (empty($divisionId)) {
                throw ValidationException::withMessages([
                    'division_id' => ['Pilih divisi/bidang yang relevan untuk video improvement sukarela.'],
                ]);
            }

            $baIncidentId = null;
        }

        $video = Video::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'video_url' => $validated['video_url'],
            'division_id' => $divisionId,
            'created_by' => $user->id,
            'creation_reason' => $validated['creation_reason'],
            'ba_incident_id' => $baIncidentId,
            'status' => 'pending_supervisor',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Video kontribusi berhasil dikirim dan menunggu verifikasi atasan.',
            'data' => $video->load(['division', 'creator', 'baIncident']),
        ], 201);
    }

    /**
     * Tampilkan detail video.
     *
     * GET /api/videos/{id}
     */
    public function show(string $id): JsonResponse
    {
        $video = Video::with([
            'division:id,name',
            'creator:id,name',
            'baIncident:id,nomor_ba,title',
            'supervisorReviewer:id,name',
            'hrReviewer:id,name',
            'postTestQuiz.questions.options',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $video,
        ]);
    }

    /**
     * Verifikasi tahap 1: Supervisor Divisi.
     *
     * PATCH /api/videos/{id}/approve-supervisor
     */
    public function approveSupervisor(Request $request, string $id, VideoApprovalService $service): JsonResponse
    {
        $video = Video::findOrFail($id);

        try {
            $updated = $service->approveSupervisor($video, $request->user(), $request->input('notes'));

            return response()->json([
                'success' => true,
                'message' => 'Video berhasil disetujui atasan dan diteruskan ke Quality/HR.',
                'data' => $updated,
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verifikasi tahap 2: HR / Quality (Publikasi).
     *
     * PATCH /api/videos/{id}/approve-hr
     */
    public function approveHr(Request $request, string $id, VideoApprovalService $service): JsonResponse
    {
        $video = Video::findOrFail($id);

        try {
            $updated = $service->approveHr($video, $request->user(), $request->input('notes'));

            return response()->json([
                'success' => true,
                'message' => 'Video berhasil disetujui HR dan telah dipublikasikan ke Knowledge Repository.',
                'data' => $updated,
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Tolak video pada tahap aktif.
     *
     * PATCH /api/videos/{id}/reject
     */
    public function reject(Request $request, string $id, VideoApprovalService $service): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $video = Video::findOrFail($id);

        try {
            $updated = $service->reject($video, $request->user(), $request->input('reason'));

            return response()->json([
                'success' => true,
                'message' => 'Video berhasil ditolak.',
                'data' => $updated,
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Tandai video telah ditonton oleh pengguna.
     * PENTING: Menonton video TIDAK memberikan poin secara langsung.
     * Poin baru masuk jika pengguna menyelesaikan dan lulus kuis post-test video.
     *
     * POST /api/videos/{id}/view
     */
    public function recordView(Request $request, string $id): JsonResponse
    {
        $video = Video::published()->findOrFail($id);

        UserVideoView::firstOrCreate([
            'user_id' => $request->user()->id,
            'video_id' => $video->id,
        ], [
            'viewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tontonan video berhasil dicatat. Kerjakan kuis post-test untuk mendapatkan poin & kontribusi KPI.',
        ]);
    }
}
