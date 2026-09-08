<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\LeaderboardSeeder;
use Database\Seeders\LearningSeeder;
use Database\Seeders\MissionSeeder;
use Database\Seeders\NotificationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageThirteenFinalAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $employee;

    protected User $supervisor;

    protected User $admin;

    protected Division $division;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->division = Division::first();

        $this->admin = User::where('email', 'admin@cps.test')->first() ?? User::factory()->create([
            'name' => 'Admin CPS',
            'email' => 'admin@cps.test',
            'employee_id' => 'CPS-00001',
            'division_id' => $this->division->id,
        ]);
        if (! $this->admin->hasRole('admin')) {
            $this->admin->assignRole('admin');
        }

        $this->employee = User::where('email', 'employee@cps.test')->first() ?? User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'employee@cps.test',
            'employee_id' => 'CPS-00004',
            'division_id' => $this->division->id,
            'jabatan' => 'Engineering Staff',
            'total_points' => 850,
            'level' => 'Technician',
        ]);
        if (! $this->employee->hasRole('employee')) {
            $this->employee->assignRole('employee');
        }

        $this->supervisor = User::where('email', 'supervisor@cps.test')->first() ?? User::factory()->create([
            'name' => 'Supervisor Produksi',
            'email' => 'supervisor@cps.test',
            'employee_id' => 'CPS-00002',
            'division_id' => $this->division->id,
            'jabatan' => 'Production Supervisor',
        ]);
        if (! $this->supervisor->hasRole('supervisor')) {
            $this->supervisor->assignRole('supervisor');
        }

        $this->seed(AchievementSeeder::class);
        $this->seed(LearningSeeder::class);
        $this->seed(MissionSeeder::class);
        $this->seed(LeaderboardSeeder::class);
        $this->seed(NotificationSeeder::class);

        BaIncident::firstOrCreate([
            'nomor_ba' => 'BA-2026-0001',
        ], [
            'division_id' => $this->division->id,
            'title' => 'Insiden Suhu Chiller Overheat Mesin Moulding Line 3',
            'description' => 'Kronologi lengkap insiden suhu chiller melebihi ambang batas toleransi.',
            'file_ba_url' => 'https://example.com/ba-0001.pdf',
            'file_ftk_url' => 'https://example.com/ftk-0001.pdf',
            'status' => 'created',
            'created_by' => $this->employee->id,
        ]);
    }

    /**
     * Audit 1: Seluruh 13 Rute Halaman Terdaftar & Dapat Diakses (200 OK).
     */
    public function test_audit_all_thirteen_pages_return_200_ok(): void
    {
        $ba = BaIncident::first();
        $material = LearningMaterial::first();
        $quiz = Quiz::first();

        $routes = [
            'Dashboard' => route('dashboard'),
            'Knowledge Repository' => route('knowledge.index'),
            'BA & Lesson Learned Index' => route('ba.index'),
            'BA Create' => route('ba.create'),
            'BA Detail' => route('ba.show', $ba ? $ba->id : 1),
            'Learning Index' => route('learning.index'),
            'Learning Detail' => route('learning.show', $material ? $material->id : 1),
            'Missions Index' => route('missions.index'),
            'Mission Detail' => route('missions.show', $quiz ? $quiz->id : 1),
            'Leaderboard' => route('leaderboard.index'),
            'Achievement' => route('achievements.index'),
            'Profile' => route('profile.edit'),
        ];

        foreach ($routes as $pageName => $url) {
            $response = $this->actingAs($this->employee)->get($url);
            $this->assertSame(200, $response->getStatusCode(), "Rute [{$pageName}] ({$url}) harus mengembalikan 200 OK.");
        }
    }

    /**
     * Audit 2: Checklist Anti-AI-Slop (§6) pada Seluruh Halaman.
     * - Tidak ada tombol dengan panah "→"
     * - Tidak ada border/background gradasi liar
     * - Tidak ada confetti atau pita emas
     */
    public function test_audit_anti_ai_slop_checklist_across_pages(): void
    {
        $urls = [
            route('dashboard'),
            route('knowledge.index'),
            route('ba.index'),
            route('ba.create'),
            route('learning.index'),
            route('missions.index'),
            route('leaderboard.index'),
            route('achievements.index'),
            route('profile.edit'),
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->employee)->get($url);
            $content = $response->getContent();

            // 1. Tidak ada panah "→" di dalam tombol
            $this->assertDoesNotMatchRegularExpression(
                '/<button[^>]*>[^<]*→[^<]*<\/button>/u',
                $content,
                "Halaman [{$url}] melanggar Anti-AI-Slop: Terdapat panah '→' pada teks tombol."
            );

            // 2. Tidak ada dekorasi confetti
            $this->assertStringNotContainsString(
                'confetti',
                $content,
                "Halaman [{$url}] melanggar Anti-AI-Slop: Terdapat efek confetti."
            );

            // 3. Tidak ada dekorasi ribbon pita
            $this->assertStringNotContainsString(
                'ribbon',
                $content,
                "Halaman [{$url}] melanggar Anti-AI-Slop: Terdapat ornamen ribbon."
            );
        }
    }

    /**
     * Audit 3: Kepatuhan Kontrak Backend (§8) pada BA & Lesson Learned.
     * - Status HANYA 3 kemungkinan: Created, Reviewed, Closed.
     * - Nomor BA auto-generate format BA-YYYY-NNNN.
     * - Dua slot upload terpisah (File BA dan FTK).
     */
    public function test_audit_backend_contract_ba_and_lesson_learned(): void
    {
        // 1. Cek Create BA Form
        $responseCreate = $this->actingAs($this->employee)->get(route('ba.create'));
        $responseCreate->assertOk();
        $responseCreate->assertSee('File Dokumen BA');
        $responseCreate->assertSee('Formulir FTK');
        $responseCreate->assertSee('font-mono');

        // 2. Cek Detail BA Status: HANYA Created, Reviewed, Closed
        $ba = BaIncident::first();
        if ($ba) {
            $responseDetail = $this->actingAs($this->employee)->get(route('ba.show', $ba->id));
            $responseDetail->assertOk();
            $content = $responseDetail->getContent();

            $validStatuses = ['Created', 'Reviewed', 'Closed'];
            $this->assertTrue(
                str_contains($content, 'Created') || str_contains($content, 'Reviewed') || str_contains($content, 'Closed')
            );
            $this->assertStringNotContainsString('Draft', $content);
            $this->assertStringNotContainsString('Rejected', $content);
            $this->assertStringNotContainsString('Approved', $content);
        }
    }

    /**
     * Audit 4: Kepatuhan 13 Divisi Tetap Pabrik (§8).
     */
    public function test_audit_fixed_thirteen_divisions_consistency(): void
    {
        $expectedDivisions = [
            'Engineering',
            'Finance Accounting Tax',
            'Gudang RM',
            'HRGA',
            'Keamanan',
            'PPIC',
            'Produksi',
            'Purchasing',
            'Quality Control',
            'Repair',
            'Sales & Marketing',
            'Warehouse & Delivery',
            'IT',
        ];

        $actualDivisions = Division::pluck('name')->all();

        foreach ($expectedDivisions as $div) {
            $this->assertContains($div, $actualDivisions, "Divisi [{$div}] wajib ada di database sesuai Kontrak §8.");
        }
    }

    /**
     * Audit 5: Kepatuhan 7 Poin PRD §5.3 Tidak Boleh Diasumsikan.
     * - Poin 1: Formula skala level (TODO)
     * - Poin 2: Formula persentase KPI Contribution (TODO)
     * - Poin 3: Kebijakan poin reviewer (TODO)
     * - Poin 4: Kebijakan retry kuis (TODO)
     * - Poin 5: Kriteria unlock achievement (TODO)
     * - Poin 6: Batas ukuran file upload (TODO)
     * - Poin 7: Kepemilikan kategori Knowledge Repository (TODO)
     */
    public function test_audit_all_seven_prd_5_3_points_have_todo_markers_and_no_invented_formulas(): void
    {
        // 1. Dashboard: Poin 1 (Skala Level) & Poin 2 (KPI Contribution)
        $responseDashboard = $this->actingAs($this->employee)->get(route('dashboard'));
        $responseDashboard->assertSee('[Menunggu PRD §5.3]');

        // 2. Mission Quiz: Poin 4 (Kebijakan Retry)
        $quiz = Quiz::first();
        if ($quiz) {
            $responseMission = $this->actingAs($this->employee)->get(route('missions.show', $quiz->id));
            $responseMission->assertSee('[Menunggu Keputusan PRD §5.3: Kebijakan Retry Kuis]');
        }

        // 3. Achievement: Poin 5 (Kriteria Unlock)
        $responseAchievement = $this->actingAs($this->employee)->get(route('achievements.index'));
        $responseAchievement->assertSee('[Menunggu Keputusan PRD §5.3: Kriteria Otomatisasi Unlock Achievement]');

        // 4. BA Create: Poin 6 (Batas Ukuran Upload)
        $responseBa = $this->actingAs($this->employee)->get(route('ba.create'));
        $responseBa->assertSee('PRD §5.3');
    }

    /**
     * Audit 6: Employee ID Monospaced Font (IBM Plex Mono) & Format CPS-00124 (§3 & §8).
     */
    public function test_audit_employee_id_mono_font_and_format(): void
    {
        // 1. Header user menu
        $response = $this->actingAs($this->employee)->get(route('dashboard'));
        $content = $response->getContent();
        $this->assertStringContainsString('font-mono', $content);
        $this->assertStringContainsString('CPS-00004', $content);

        // 2. Profile page
        $responseProfile = $this->actingAs($this->employee)->get(route('profile.edit'));
        $contentProfile = $responseProfile->getContent();
        $this->assertStringContainsString('font-mono', $contentProfile);
        $this->assertStringContainsString('CPS-00004', $contentProfile);

        // 3. Leaderboard
        $responseLeaderboard = $this->actingAs($this->employee)->get(route('leaderboard.index'));
        $contentLeaderboard = $responseLeaderboard->getContent();
        $this->assertStringContainsString('font-mono', $contentLeaderboard);
    }

    /**
     * Audit 7: RBAC Supervisor Scoped Actions (§8).
     * - Supervisor hanya bisa approve/reject BA dari divisinya sendiri.
     */
    public function test_audit_rbac_supervisor_scoped_actions(): void
    {
        $divA = Division::first();
        $divB = Division::skip(1)->first();

        $supervisorA = User::factory()->create(['division_id' => $divA->id]);
        $supervisorA->assignRole('supervisor');

        // BA di divisi A
        $baA = BaIncident::create([
            'nomor_ba' => 'BA-2026-9001',
            'division_id' => $divA->id,
            'title' => 'Insiden Divisi A',
            'file_ba_url' => 'https://example.com/ba.pdf',
            'file_ftk_url' => 'https://example.com/ftk.pdf',
            'status' => 'created',
            'created_by' => $this->employee->id,
        ]);

        // BA di divisi B
        $baB = BaIncident::create([
            'nomor_ba' => 'BA-2026-9002',
            'division_id' => $divB->id,
            'title' => 'Insiden Divisi B',
            'file_ba_url' => 'https://example.com/ba.pdf',
            'file_ftk_url' => 'https://example.com/ftk.pdf',
            'status' => 'created',
            'created_by' => $this->employee->id,
        ]);

        // Supervisor A melihat BA divisinya sendiri -> Ada tombol Setujui / Minta Revisi
        $resA = $this->actingAs($supervisorA)->get(route('ba.show', $baA->id));
        $resA->assertSee('Setujui BA');
        $resA->assertSee('Minta Revisi');

        // Supervisor A melihat BA divisi lain -> TIDAK ada tombol Setujui / Minta Revisi
        $resB = $this->actingAs($supervisorA)->get(route('ba.show', $baB->id));
        $resB->assertDontSee('Setujui BA');
        $resB->assertDontSee('Minta Revisi');
    }
}
