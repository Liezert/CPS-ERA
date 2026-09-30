<?php

namespace App\Livewire;

use App\Enums\BaIncidentStatus;
use App\Models\BaIncident;
use App\Models\GoogleDriveToken;
use App\Models\KnowledgeDocument;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserLearningProgress;
use App\Models\Video;
use App\Policies\BaIncidentPolicy;
use App\Services\KpiContributionCalculator;
use App\Services\VideoApprovalService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard - CPS ERA')]
class Dashboard extends Component
{
    public function render()
    {
        /** @var User $user */
        $user = Auth::user()->load('division');

        // Inisial avatar pengguna
        $initials = 'CP';
        if ($user->name) {
            $parts = explode(' ', trim($user->name));
            $initials = strtoupper(substr($parts[0], 0, 1).(isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
        }

        // HR (admin/quality) cukup melihat tugas review & kelola data; kartu belajar/misi/poin milik
        // karyawan tidak relevan bagi mereka (keputusan owner 2026-09-27).
        if ($user->hasAnyRole(BaIncidentPolicy::HR_REVIEWER_ROLES)) {
            return view('livewire.dashboard', [
                'user' => $user,
                'initials' => $initials,
                'hrView' => true,
                'reviewQueue' => $this->reviewQueue($user),
                'adminShortcuts' => $user->hasRole('admin') ? $this->adminShortcuts() : [],
                // Unggahan video bergantung pada koneksi Drive: tampilkan statusnya langsung ke admin.
                'driveConnection' => $user->hasRole('admin') ? GoogleDriveToken::active() : null,
            ]);
        }

        // 1. XP dari users.xp (Dual-ledger gamification)
        $xp = (int) ($user->xp ?? 0);

        // 2. User KPI Yearly (Tabel cache/agregat tahun berjalan)
        $kpiYearly = $user->getKpiYearly();

        // 3. Kalkulasi Progress Belajar (% materi yang ditandai selesai)
        $learningProgress = (int) round(
            UserLearningProgress::where('user_id', $user->id)
                ->avg('progress_percent') ?? 0
        );

        // 4. Peringkat Leaderboard (urutan XP yang sama dengan halaman Leaderboard)
        $leaderboardRank = User::onLeaderboard()->where('xp', '>', $xp)->count() + 1;

        // 5. Knowledge Repository Terbaru (3 item terpublikasi untuk layout grid kartu)
        $latestKnowledge = KnowledgeDocument::with(['division', 'creator', 'topic'])
            ->published()
            ->latest()
            ->take(3)
            ->get();

        // 6. BA & Lesson Learned Terbaru (5 item)
        $latestBa = BaIncident::visibleTo($user)
            ->with(['division', 'creator'])
            ->latest()
            ->take(5)
            ->get();

        // 7. Materi Belajar yang Sedang Dipelajari (In-Progress: > 0% dan < 100%)
        $continueLearning = UserLearningProgress::where('user_id', $user->id)
            ->where('progress_percent', '>', 0)
            ->where('progress_percent', '<', 100)
            ->with('material.category')
            ->latest('updated_at')
            ->first();

        // 8. Misi yang Perlu Diselesaikan (Belum Selesai/Lulus, Tertua Lebih Dulu)
        $pendingMission = Quiz::missions()
            ->whereDoesntHave('attempts', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('passed', true);
            })
            ->withCount('questions')
            ->oldest('created_at')
            ->first();

        return view('livewire.dashboard', [
            'user' => $user,
            'initials' => $initials,
            'hrView' => false,
            'xp' => $xp,
            'kpiYearly' => $kpiYearly,
            'kpi' => app(KpiContributionCalculator::class)->calculate($user),
            'reviewQueue' => $this->reviewQueue($user),
            'adminShortcuts' => [],
            'driveConnection' => null,
            'learningProgress' => $learningProgress,
            'leaderboardRank' => $leaderboardRank,
            'latestKnowledge' => $latestKnowledge,
            'latestBa' => $latestBa,
            'continueLearning' => $continueLearning,
            'pendingMission' => $pendingMission,
        ]);
    }

    /**
     * Antrean review milik user ini (null jika user bukan reviewer), dipisah per jenis:
     * - capa: kriteria sama dengan BaIncidentPolicy (supervisor = divisinya sendiri di tahap Supervisor,
     *   HR = tahap HR kecuali laporan yang ia setujui sendiri di tahap Supervisor).
     * - video: hanya untuk HR (VideoPolicy::review), bukan video unggahannya sendiri. Null bila bukan HR.
     *
     * @return array{capa: array{count: int, items: Collection<int, BaIncident>}, video: array{count: int, items: Collection<int, Video>}|null}|null
     */
    private function reviewQueue(User $user): ?array
    {
        $isSupervisor = $user->hasRole('supervisor');
        $isHr = $user->hasAnyRole(BaIncidentPolicy::HR_REVIEWER_ROLES);

        if (! $isSupervisor && ! $isHr) {
            return null;
        }

        $capa = BaIncident::query()->where(function (Builder $query) use ($user, $isSupervisor, $isHr): void {
            if ($isSupervisor) {
                $query->orWhere(fn (Builder $q) => $q
                    ->where('status', BaIncidentStatus::PendingSupervisor->value)
                    ->where('division_id', $user->division_id));
            }

            if ($isHr) {
                $query->orWhere(fn (Builder $q) => $q
                    ->where('status', BaIncidentStatus::PendingHr->value)
                    ->where(fn (Builder $q) => $q->whereNull('supervisor_reviewed_by')->orWhere('supervisor_reviewed_by', '!=', $user->id)));
            }
        });

        $video = Video::query()
            ->where('creation_reason', VideoApprovalService::CREATION_REASON)
            ->where('status', 'pending_hr')
            ->where('created_by', '!=', $user->id);

        return [
            'capa' => [
                'count' => (clone $capa)->count(),
                'items' => $capa->with(['division', 'creator'])->oldest('updated_at')->take(5)->get(),
            ],
            'video' => $isHr ? [
                'count' => (clone $video)->count(),
                'items' => $video->with(['division', 'creator'])->oldest()->take(5)->get(),
            ] : null,
        ];
    }

    /**
     * Pintasan kelola data khusus admin (halaman di panel /admin), menggantikan dashboard panel admin.
     *
     * @return list<array{group: string, label: string, description: string, url: string, icon: string}>
     */
    private function adminShortcuts(): array
    {
        return array_values(array_filter([
            ['group' => 'Learning & Pengembangan', 'label' => 'Tambah Materi', 'description' => LearningMaterial::count().' materi Learning', 'url' => route('filament.admin.resources.learning-materials.create'), 'icon' => 'academic'],
            ['group' => 'Learning & Pengembangan', 'label' => 'Buat Quiz & Misi', 'description' => Quiz::count().' quiz & post-test', 'url' => route('filament.admin.resources.quizzes.create'), 'icon' => 'puzzle'],
            ['group' => 'Learning & Pengembangan', 'label' => 'Kategori & Topik', 'description' => 'Kategori Learning & topik Knowledge', 'url' => route('taxonomy.index'), 'icon' => 'folder-cog'],
            ['group' => 'Knowledge & Video', 'label' => 'Tambah Dokumen', 'description' => KnowledgeDocument::count().' dokumen Knowledge Repository', 'url' => route('filament.admin.resources.knowledge-documents.create'), 'icon' => 'book'],
            ['group' => 'Knowledge & Video', 'label' => 'Video Kontribusi', 'description' => 'Review & unggah video', 'url' => route('videos.index'), 'icon' => 'video'],
            ['group' => 'SDM & Laporan', 'label' => 'Karyawan & XP', 'description' => 'Akun, kata sandi, koreksi XP', 'url' => route('filament.admin.resources.users.index'), 'icon' => 'user'],
            ['group' => 'SDM & Laporan', 'label' => 'Rekap Poin CPS ERA', 'description' => 'Poin tahunan karyawan', 'url' => route('filament.admin.resources.user-kpi-yearlies.index'), 'icon' => 'badge-check'],
            ['group' => 'SDM & Laporan', 'label' => 'Semua Laporan CAPA', 'description' => BaIncident::count().' laporan tercatat', 'url' => route('filament.admin.resources.ba-incidents.index'), 'icon' => 'shield-alert'],
            config('app.achievements_enabled') ? ['group' => 'SDM & Laporan', 'label' => 'Badge & Achievement', 'description' => 'Katalog lencana', 'url' => route('filament.admin.resources.achievements.index'), 'icon' => 'check'] : null,
            // Advance: pengaturan sekali setel yang berdampak ke seluruh karyawan.
            ['group' => 'Advance', 'label' => 'Target KPI Materi', 'description' => 'Mengubah periode/target langsung memengaruhi progres KPI seluruh karyawan.', 'url' => route('filament.admin.resources.kpi-settings.index'), 'icon' => 'chart'],
            ['group' => 'Advance', 'label' => 'Google Drive', 'description' => 'Akun penyimpanan semua video & dokumen. Mengganti akun memutus unggahan sampai terhubung lagi.', 'url' => route('filament.admin.pages.google-drive'), 'icon' => 'cog'],
        ]));
    }
}
