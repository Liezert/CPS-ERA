<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BaIncident;
use App\Models\User;
use App\Services\BaIncidentService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BaIncidentController extends Controller
{
    public function __construct(
        protected BaIncidentService $baIncidentService,
    ) {}

    /**
     * Display a listing of BA incidents, scoped by role and filtered by parameters.
     * GET /api/ba-incidents?status=&division=&q=
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = BaIncident::with(['division:id,name', 'creator:id,name,email', 'reviewer:id,name,email']);

        // Role scoping per PRD 2.2:
        // Admin & Quality dapat melihat lintas divisi.
        // Supervisor & Employee dibatasi pada divisinya sendiri.
        if (! $user->hasAnyRole(['admin', 'quality'])) {
            $query->where('division_id', $user->division_id);
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter division
        if ($request->filled('division')) {
            $divisionFilter = $request->input('division');
            if (is_numeric($divisionFilter)) {
                $query->where('division_id', $divisionFilter);
            } else {
                $query->whereHas('division', function ($q) use ($divisionFilter): void {
                    $q->where('name', 'like', "%{$divisionFilter}%");
                });
            }
        }

        // Search by nomor_ba
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where('nomor_ba', 'like', "%{$search}%");
        }

        $incidents = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $incidents,
        ]);
    }

    /**
     * Store a newly created BA incident.
     * POST /api/ba-incidents
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', BaIncident::class);

        /** @var User $user */
        $user = $request->user();

        // Fallback otomatis ke division_id user jika tidak ditentukan
        if (! $request->filled('division_id') && $user?->division_id) {
            $request->merge(['division_id' => $user->division_id]);
        }

        $rules = [
            'division_id' => ['required', 'exists:divisions,id'],
            'file_ba' => [
                'required_without:file_ba_url',
                $request->hasFile('file_ba') ? 'file' : 'string',
            ],
            'file_ba_url' => ['nullable', 'string', 'required_without:file_ba'],
            'file_ftk' => [
                'required_without:file_ftk_url',
                $request->hasFile('file_ftk') ? 'file' : 'string',
            ],
            'file_ftk_url' => ['nullable', 'string', 'required_without:file_ftk'],
        ];

        if ($request->hasFile('file_ba')) {
            $rules['file_ba'][] = 'mimes:pdf,doc,docx,jpg,jpeg,png';
            $rules['file_ba'][] = 'max:20480';
        }

        if ($request->hasFile('file_ftk')) {
            $rules['file_ftk'][] = 'mimes:pdf,doc,docx,jpg,jpeg,png';
            $rules['file_ftk'][] = 'max:20480';
        }

        $validated = $request->validate($rules, [
            'division_id.required' => 'Divisi wajib diisi.',
            'division_id.exists' => 'Divisi yang dipilih tidak valid.',
            'file_ba.required_without' => 'File BA wajib diunggah.',
            'file_ftk.required_without' => 'File FTK wajib diunggah.',
        ]);

        $fileBa = $request->file('file_ba') ?? $request->input('file_ba') ?? $request->input('file_ba_url');
        $fileFtk = $request->file('file_ftk') ?? $request->input('file_ftk') ?? $request->input('file_ftk_url');

        $incident = $this->baIncidentService->create(
            $validated,
            $user,
            $fileBa,
            $fileFtk
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan BA berhasil dibuat.',
            'data' => $incident->load(['division', 'creator', 'activityLogs']),
        ], 201);
    }

    /**
     * Display the specified BA incident detail with activity logs.
     * GET /api/ba-incidents/{id}
     */
    public function show(string $id): JsonResponse
    {
        $incident = BaIncident::with([
            'division',
            'creator:id,name,email',
            'reviewer:id,name,email',
            'activityLogs.actor:id,name',
            'lessonLearned',
        ])->findOrFail($id);

        Gate::authorize('view', $incident);

        return response()->json([
            'success' => true,
            'data' => $incident,
        ]);
    }

    /**
     * Review the specified BA incident (created -> reviewed).
     * PATCH /api/ba-incidents/{id}/review
     */
    public function review(Request $request, string $id): JsonResponse
    {
        $incident = BaIncident::findOrFail($id);

        Gate::authorize('review', $incident);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            /** @var User $user */
            $user = $request->user();
            $updated = $this->baIncidentService->review($incident, $user, $validated['note'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'BA berhasil ditinjau. Dokumen Lesson Learned otomatis diterbitkan.',
                'data' => $updated->load(['division', 'reviewer', 'lessonLearned']),
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Close the specified BA incident (reviewed -> closed).
     * PATCH /api/ba-incidents/{id}/close
     */
    public function close(Request $request, string $id): JsonResponse
    {
        $incident = BaIncident::findOrFail($id);

        Gate::authorize('close', $incident);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            /** @var User $user */
            $user = $request->user();
            $updated = $this->baIncidentService->close($incident, $user, $validated['note'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'BA berhasil ditutup dan diselesaikan.',
                'data' => $updated->load(['division', 'reviewer']),
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the activity log history of a BA incident.
     * GET /api/ba-incidents/{id}/activity-log
     */
    public function activityLog(string $id): JsonResponse
    {
        $incident = BaIncident::findOrFail($id);

        Gate::authorize('view', $incident);

        $logs = $incident->activityLogs()->with('actor:id,name,email')->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
