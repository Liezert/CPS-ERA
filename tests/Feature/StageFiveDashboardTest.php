<?php

namespace Tests\Feature;

use App\Models\BaIncident;
use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserLearningProgress;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StageFiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    /**
     * DoD #1: Header memuat sapaan nama user, avatar inisial, chip level (brand-tint), dan tombol aksi utama.
     */
    public function test_dashboard_header_renders_user_greeting_avatar_level_chip_and_main_action(): void
    {
        $division = Division::first();
        $user = User::factory()->create([
            'name' => 'Ahmad Dahlan',
            'employee_id' => 'CPS-00222',
            'division_id' => $division->id,
            'jabatan' => 'Leader Produksi',
            'level' => 2,
        ]);
        $user->assignRole('employee');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        // Sapaan user & identitas
        $response->assertSee('Selamat bertugas, Ahmad Dahlan');
        $response->assertSee('CPS-00222');
        $response->assertSee('Leader Produksi');
        $response->assertSee($division->name);

        // Avatar inisial
        $response->assertSee('AD'); // Inisial dari Ahmad Dahlan
        $response->assertSee('bg-brand-tint', false);

        // Chip level (brand-tint) & TODO PRD §5.3
        $response->assertSee('Level 2');
        $response->assertSee('TODO: Menunggu keputusan PRD §5.3 (Poin 1: Formula skala level)', false);

        // Tombol aksi utama "Buat BA Baru" tanpa panah
        $response->assertSee('Buat BA Baru');
        $this->assertStringNotContainsString('Buat BA Baru &rarr;', $response->getContent());
        $this->assertStringNotContainsString('Buat BA Baru →', $response->getContent());
    }

    /**
     * DoD #2 & #4: Poin dihitung murni sebagai hasil agregasi ledger point_transactions.
     */
    public function test_dashboard_displays_points_aggregated_from_ledger_transactions(): void
    {
        $user = User::factory()->create([
            'employee_id' => 'CPS-00333',
            'total_points' => 0, // Sengaja diisi 0 di tabel users untuk membuktikan agregasi ledger
        ]);
        $user->assignRole('employee');

        // Buat 2 transaksi poin di ledger
        PointTransaction::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'points' => 150,
            'source_type' => 'ba_submission',
            'description' => 'Poin pelaporan BA Mesin A',
        ]);

        PointTransaction::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'points' => 50,
            'source_type' => 'learning_completion',
            'description' => 'Menyelesaikan modul SOP Keselamatan',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        // Total harus 200 (150 + 50) dari ledger
        $response->assertSee('200 Pts');
        $response->assertSee('Agregasi ledger point_transactions');
    }

    /**
     * DoD #2: Grid metric card memuat Learning Progress %, KPI Contribution % (dengan TODO), dan progress bar level.
     */
    public function test_dashboard_metric_cards_and_progress_bars(): void
    {
        $user = User::factory()->create(['level' => 2]);
        $user->assignRole('employee');

        $category = LearningCategory::create([
            'name' => 'Keselamatan Kerja',
            'created_by' => $user->id,
            'description' => 'SOP K3',
        ]);

        $material = LearningMaterial::create([
            'id' => Str::uuid()->toString(),
            'learning_category_id' => $category->id,
            'title' => 'SOP Kebakaran 101',
            'type' => 'dokumen',
            'created_by' => $user->id,
            'status' => 'published',
        ]);

        UserLearningProgress::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'learning_material_id' => $material->id,
            'progress_percent' => 80,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        // 1. Learning progress card
        $response->assertSee('Learning Progress');
        $response->assertSee('80%');

        // 2. KPI Contribution card (TODO PRD §5.3)
        $response->assertSee('KPI Contribution');
        $response->assertSee('--');
        $response->assertSee('[Menunggu PRD §5.3]');
        $response->assertSee('TODO: Menunggu keputusan PRD §5.3 (Poin 2: Formula persentase KPI Contribution)', false);

        // 3. Target Level Berikutnya & progress bar hijau
        $response->assertSee('Target Level Berikutnya');
        $response->assertSee('Level 3');
        $response->assertSee('bg-brand h-2 rounded-full', false);
    }

    /**
     * DoD #3 & #5: Dua kolom list (hairline divider, bukan card-soup) dan status BA pakai badge sesuai §5.
     */
    public function test_dashboard_two_columns_list_with_hairline_dividers_and_status_badges(): void
    {
        $division = Division::first();
        $user = User::factory()->create(['division_id' => $division->id]);
        $user->assignRole('supervisor');

        // Buat data Knowledge Repository
        KnowledgeDocument::create([
            'id' => Str::uuid()->toString(),
            'title' => 'SOP Penanganan Limbah B3 Pabrik',
            'division_id' => $division->id,
            'type' => 'dokumen',
            'created_by' => $user->id,
            'status' => 'published',
        ]);

        // Buat data BA dengan 3 status berbeda
        BaIncident::create([
            'id' => Str::uuid()->toString(),
            'nomor_ba' => 'BA-2026-0001',
            'title' => 'Insiden Oli Mesin Bocor',
            'division_id' => $division->id,
            'file_ba_url' => '/files/ba-0001.pdf',
            'file_ftk_url' => '/files/ftk-0001.pdf',
            'created_by' => $user->id,
            'status' => 'created',
        ]);

        BaIncident::create([
            'id' => Str::uuid()->toString(),
            'nomor_ba' => 'BA-2026-0002',
            'title' => 'Insiden Sensor Conveyor Error',
            'division_id' => $division->id,
            'file_ba_url' => '/files/ba-0002.pdf',
            'file_ftk_url' => '/files/ftk-0002.pdf',
            'created_by' => $user->id,
            'status' => 'reviewed',
        ]);

        BaIncident::create([
            'id' => Str::uuid()->toString(),
            'nomor_ba' => 'BA-2026-0003',
            'title' => 'Insiden Pipa Kompresor Retak',
            'division_id' => $division->id,
            'file_ba_url' => '/files/ba-0003.pdf',
            'file_ftk_url' => '/files/ftk-0003.pdf',
            'created_by' => $user->id,
            'status' => 'closed',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        // Header list dua kolom
        $response->assertSee('Knowledge Repository Terbaru');
        $response->assertSee('BA &amp; Lesson Learned Terbaru', false);

        // Hairline divider tipis (divide-y divide-neutral-200) bukan card individual
        $response->assertSee('divide-y divide-neutral-200', false);

        // Verifikasi item Knowledge
        $response->assertSee('SOP Penanganan Limbah B3 Pabrik');

        // Verifikasi item BA & nomor BA format BA-YYYY-NNNN
        $response->assertSee('BA-2026-0001');
        $response->assertSee('Insiden Oli Mesin Bocor');
        $response->assertSee('BA-2026-0002');
        $response->assertSee('BA-2026-0003');

        // Verifikasi status badge bersudut tegas 2px per Design System §5
        $response->assertSee('rounded-badge', false);
        $response->assertSee('Created');
        $response->assertSee('Reviewed');
        $response->assertSee('Closed');
    }

    /**
     * DoD Anti-AI-Slop: Tidak ada card-soup dengan drop-shadow berat pada daftar item list.
     */
    public function test_dashboard_passes_anti_ai_slop_card_soup_checklist(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employee');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Tidak boleh ada shadow-lg / shadow-md berulang pada list item
        $this->assertStringNotContainsString('shadow-lg rounded-xl p-4 mb-3', $content);
        $this->assertStringNotContainsString('shadow-xl', $content);

        // Tidak boleh ada gradasi abstrak
        $this->assertStringNotContainsString('bg-gradient-to-', $content);
    }
}
