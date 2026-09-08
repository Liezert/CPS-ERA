<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageThreeLayoutShellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    /**
     * DoD #1: Sidebar berfungsi di 3 breakpoint (Desktop full, Tablet icon-only, Mobile drawer + bottom-nav).
     */
    public function test_sidebar_supports_three_breakpoints_desktop_tablet_mobile(): void
    {
        $user = User::factory()->create([
            'employee_id' => 'CPS-00101',
        ]);
        $user->assignRole('employee');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        // 1. Desktop full sidebar (w-64)
        $response->assertSee('desktop:w-64', false);
        $response->assertSee('hidden desktop:inline', false);

        // 2. Tablet icon-only sidebar (w-20)
        $response->assertSee('tablet:w-20', false);
        $response->assertSee('hidden tablet:flex', false);

        // 3. Mobile drawer (off-canvas slide-over with Alpine.js x-show)
        $response->assertSee('x-show="mobileMenuOpen"', false);
        $response->assertSee('mobileMenuOpen = false', false);

        // 4. Mobile bottom navigation bar
        $response->assertSee('tablet:hidden fixed bottom-0', false);
        $response->assertSee('Navigasi cepat mobile', false);
    }

    /**
     * DoD #2: Menu sidebar berbeda sesuai role (RBAC) — Employee.
     * Scope: Menu standar, tidak melihat Kategori Learning atau Master Data.
     */
    public function test_sidebar_menu_displays_correctly_for_employee_role(): void
    {
        $user = User::factory()->create(['employee_id' => 'CPS-00102']);
        $user->assignRole('employee');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        // Menu standar harus terlihat
        $response->assertSee('Dashboard');
        $response->assertSee('Knowledge Repository');
        $response->assertSee('BA &amp; Lesson Learned', false);
        $response->assertSee('Learning');
        $response->assertSee('Mission &amp; Game', false);
        $response->assertSee('Leaderboard');
        $response->assertSee('Achievement');

        // Menu supervisor/admin/quality TIDAK boleh tampil
        $response->assertDontSee('Kategori Learning');
        $response->assertDontSee('Panel Admin Filament');
        $response->assertDontSee('Master Data (Filament)');
    }

    /**
     * DoD #2: Menu sidebar berbeda sesuai role (RBAC) — Supervisor.
     * Scope: Menu standar dengan badge "Divisi" pada BA, tidak melihat panel admin.
     */
    public function test_sidebar_menu_displays_correctly_for_supervisor_role(): void
    {
        $division = Division::first();
        $user = User::factory()->create([
            'employee_id' => 'CPS-00103',
            'division_id' => $division->id,
        ]);
        $user->assignRole('supervisor');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        // Menu standar
        $response->assertSee('Dashboard');
        $response->assertSee('BA &amp; Lesson Learned', false);
        $response->assertSee('Divisi'); // Badge khusus supervisor

        // Tidak boleh melihat menu khusus Quality / Admin
        $response->assertDontSee('Kategori Learning');
        $response->assertDontSee('Master Data (Filament)');
    }

    /**
     * DoD #2: Menu sidebar berbeda sesuai role (RBAC) — Quality.
     * Scope: Menu standar + Kategori Learning (Validasi), tidak melihat Panel Admin.
     */
    public function test_sidebar_menu_displays_correctly_for_quality_role(): void
    {
        $user = User::factory()->create(['employee_id' => 'CPS-00104']);
        $user->assignRole('quality');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        // Menu standar + Kategori Learning
        $response->assertSee('Dashboard');
        $response->assertSee('Kategori Learning');

        // Tidak boleh melihat Master Data Filament
        $response->assertDontSee('Master Data (Filament)');
    }

    /**
     * DoD #2: Menu sidebar berbeda sesuai role (RBAC) — Admin.
     * Scope: Full Access (Menu standar + Kategori Learning + Master Data Filament).
     */
    public function test_sidebar_menu_displays_correctly_for_admin_role(): void
    {
        $user = User::factory()->create(['employee_id' => 'CPS-00105']);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        // Menu standar + Kategori Learning + Master Data
        $response->assertSee('Dashboard');
        $response->assertSee('Kategori Learning');
        $response->assertSee('Master Data');
        $response->assertSee('Filament');
    }

    /**
     * DoD #3: Header + notifikasi terpasang.
     * - Bell icon notifikasi dengan dropdown list ber-divider tipis (Design System §5).
     * - Menu profile dengan nama, employee ID (font-mono), dan badge role.
     */
    public function test_header_and_notification_dropdown_render_correctly(): void
    {
        $division = Division::first();
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'employee_id' => 'CPS-00999',
            'division_id' => $division->id,
        ]);
        $user->assignRole('employee');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        // 1. Mobile hamburger button
        $response->assertSee('aria-label="Buka navigasi menu"', false);

        // 2. Notification bell & dropdown list (hairline divider per §5)
        $response->assertSee('aria-label="Lihat notifikasi"', false);
        $response->assertSee('divide-y divide-neutral-200', false);
        $response->assertSee('Notifikasi');
        $response->assertSee('Tandai dibaca');
        $response->assertSee('Lihat Semua Notifikasi');

        // 3. Profile dropdown with font-mono Employee ID & Role badge
        $response->assertSee('Budi Santoso');
        $response->assertSee('CPS-00999');
        $response->assertSee('font-mono', false);
        $response->assertSee('Profil & Pengaturan Akun');
        $response->assertSee('Keluar dari Sistem');
    }

    /**
     * DoD #1 (Tambahan): Mobile bottom nav memiliki 5 akses cepat jempol.
     */
    public function test_mobile_bottom_navigation_has_five_quick_actions(): void
    {
        $user = User::factory()->create(['employee_id' => 'CPS-00106']);
        $user->assignRole('employee');

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        // 5 item cepat: Beranda, Knowledge, BA Insiden, Learning, Menu
        $response->assertSee('Beranda');
        $response->assertSee('Knowledge');
        $response->assertSee('BA Insiden');
        $response->assertSee('Learning');
        $response->assertSee('Menu');
    }
}
