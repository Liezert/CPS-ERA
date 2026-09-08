<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StageOneScaffoldingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    /**
     * DoD #1: tailwind.config.js berisi token sesuai Design System §2–§4.
     */
    public function test_dod_1_tailwind_config_has_all_design_system_tokens(): void
    {
        $tailwindPath = base_path('tailwind.config.js');
        $this->assertFileExists($tailwindPath);

        $content = File::get($tailwindPath);

        // §2 Warna
        $this->assertStringContainsString('#0B7840', $content, 'Warna brand #0B7840 harus ada.');
        $this->assertStringContainsString('#085C30', $content, 'Warna brand-dark #085C30 harus ada.');
        $this->assertStringContainsString('#E8F5EC', $content, 'Warna brand-tint #E8F5EC harus ada.');
        $this->assertStringContainsString('#FAFAFA', $content, 'Warna neutral-50 harus ada.');
        $this->assertStringContainsString('#E4E4E7', $content, 'Warna neutral-200 harus ada.');
        $this->assertStringContainsString('#71717A', $content, 'Warna neutral-500 harus ada.');
        $this->assertStringContainsString('#18181B', $content, 'Warna neutral-900 harus ada.');
        $this->assertStringContainsString('#FFFFFF', $content, 'Warna white harus ada.');

        // §3 Tipografi
        $this->assertStringContainsString('Inter', $content, 'Font sans Inter harus terdaftar.');
        $this->assertStringContainsString('IBM Plex Mono', $content, 'Font mono IBM Plex Mono harus terdaftar.');
        $this->assertStringNotContainsString('Roboto Slab', $content, 'Roboto Slab wajib dihapus sesuai §3.');

        // §4 Radius & Breakpoints
        $this->assertStringContainsString("'2px'", $content, 'Token radius 2px badge harus ada.');
        $this->assertStringContainsString('375px', $content, 'Breakpoint mobile 375px harus ada.');
        $this->assertStringContainsString('820px', $content, 'Breakpoint tablet 820px harus ada.');
        $this->assertStringContainsString('1440px', $content, 'Breakpoint desktop 1440px harus ada.');

        // Content scanning Livewire
        $this->assertStringContainsString('./app/Livewire/**/*.php', $content, 'Livewire content scan harus ada.');
    }

    /**
     * DoD #2: Font Inter & IBM Plex Mono termuat dan tampil benar di layouts.
     */
    public function test_dod_2_fonts_are_loaded_cleanly_in_layouts(): void
    {
        $appLayout = File::get(resource_path('views/layouts/app.blade.php'));
        $guestLayout = File::get(resource_path('views/layouts/guest.blade.php'));

        foreach ([$appLayout, $guestLayout] as $layout) {
            $this->assertStringContainsString('inter', $layout);
            $this->assertStringContainsString('ibm-plex-mono', $layout);
            $this->assertStringNotContainsString('roboto-slab', $layout, 'Roboto Slab tidak boleh dimuat di layout.');
        }
    }

    /**
     * DoD #3: Struktur folder Blade/Livewire disepakati & eksis di filesystem.
     */
    public function test_dod_3_blade_and_livewire_folder_structures_exist(): void
    {
        // Blade component directories
        $this->assertDirectoryExists(resource_path('views/components/layout'));
        $this->assertDirectoryExists(resource_path('views/components/ui'));

        // Livewire component class directories
        $livewireModules = [
            'Knowledge',
            'Ba',
            'Learning',
            'Mission',
            'Leaderboard',
            'Notification',
            'Achievement',
            'Profile',
        ];

        foreach ($livewireModules as $module) {
            $this->assertDirectoryExists(app_path("Livewire/{$module}"));
            $this->assertDirectoryExists(resource_path('views/livewire/'.strtolower($module)));
        }
    }

    /**
     * DoD #4: Middleware role-based routing berjalan (redirect dasar per role & otorisasi).
     */
    public function test_dod_4_role_based_routing_and_redirects(): void
    {
        $division = Division::where('name', 'IT')->firstOrFail();

        // 1. Guest akses dashboard -> redirect ke login
        $guestResponse = $this->get('/dashboard');
        $guestResponse->assertRedirect('/login');

        // 2. User terotentikasi akses / -> redirect ke dashboard
        $employee = User::factory()->create(['division_id' => $division->id]);
        $employee->assignRole('employee');

        $rootResponse = $this->actingAs($employee)->get('/');
        $rootResponse->assertRedirect(route('dashboard'));

        // 3. Employee & Supervisor DITOLAK dari route khusus Quality/Admin (403)
        $empAccess = $this->actingAs($employee)->get('/management/learning-categories');
        $empAccess->assertStatus(403);

        $supervisor = User::factory()->create(['division_id' => $division->id]);
        $supervisor->assignRole('supervisor');
        $spvAccess = $this->actingAs($supervisor)->get('/management/learning-categories');
        $spvAccess->assertStatus(403);

        // 4. Quality & Admin DIIZINKAN mengakses route management (200 OK)
        $quality = User::factory()->create(['division_id' => $division->id]);
        $quality->assignRole('quality');
        $qtyAccess = $this->actingAs($quality)->get('/management/learning-categories');
        $qtyAccess->assertOk();

        $admin = User::factory()->create(['division_id' => $division->id]);
        $admin->assignRole('admin');
        $admAccess = $this->actingAs($admin)->get('/management/learning-categories');
        $admAccess->assertOk();
    }
}
