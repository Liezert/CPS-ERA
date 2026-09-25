<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageFourAuthAndRbacRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    /**
     * DoD #1: Halaman login dapat dimuat dengan layout satu panel putih & logo resmi CPS.
     */
    public function test_login_screen_renders_with_official_cps_logo_and_single_white_panel(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        // Logo resmi CPS mark & wordmark per §2
        $response->assertSee('CPS', false);
        $response->assertSee('CPS ERA Hub', false);
        $response->assertSee('PT Catur Pilar Sejahtera', false);

        // Panel putih tunggal border 1px neutral-200
        $response->assertSee('bg-white border border-neutral-200 rounded-lg', false);

        // Input Email & Password
        $response->assertSee('Email Pegawai');
        $response->assertSee('Password');

        // Tombol menggunakan komponen Stage 2 dengan teks "Masuk ke Sistem"
        $response->assertSee('Masuk ke Sistem');
    }

    /**
     * DoD #1 & #3: Checklist Anti-AI-Slop Design System §6 lulus di halaman login.
     */
    public function test_login_screen_passes_anti_ai_slop_checklist(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Tidak ada simbol panah "→" di teks tombol
        $this->assertStringNotContainsString('→', $content, 'Tombol tidak boleh memiliki simbol panah!');

        // 2. Tidak ada split layout 50:50 dark/light
        $this->assertStringNotContainsString('dark:bg-gray-900', $content, 'Latar belakang tidak boleh menggunakan tema gelap split!');

        // 3. Tidak ada gradasi abstrak atau hiasan tanpa fungsi
        $this->assertStringNotContainsString('bg-gradient-to-', $content, 'Tidak boleh ada gradasi dekoratif abstrak!');

        // 4. Tidak ada warna indigo peninggalan Breeze default
        $this->assertStringNotContainsString('text-indigo-600', $content, 'Warna default indigo Breeze harus dihapus!');
    }

    /**
     * DoD #1: Login dan Logout berfungsi dengan status session yang benar.
     */
    public function test_login_and_logout_functions_properly(): void
    {
        $user = User::factory()->create([
            'email' => 'operator@caturpilar.com',
            'employee_id' => 'CPS-00101',
            'password' => bcrypt('rahasia123'),
        ]);
        $user->assignRole('employee');

        // 1. Login
        $loginResponse = $this->post(route('login'), [
            'email' => 'operator@caturpilar.com',
            'password' => 'rahasia123',
        ]);

        $this->assertAuthenticatedAs($user);
        $loginResponse->assertRedirect(route('dashboard'));

        // 2. Logout
        $logoutResponse = $this->post(route('logout'));
        $this->assertGuest();
        $logoutResponse->assertRedirect('/');
    }

    /**
     * DoD #2: Autentikasi mendukung NIK Pegawai (format CPS-XXXXX).
     */
    public function test_user_can_authenticate_using_employee_id(): void
    {
        $user = User::factory()->create([
            'email' => 'operator2@caturpilar.com',
            'employee_id' => 'CPS-00777',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('employee');

        $response = $this->post(route('login'), [
            'email' => 'CPS-00777', // Input NIK pada field email/NIK
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    /**
     * DoD #2: Redirect setelah login sesuai role — Employee -> /dashboard.
     */
    public function test_employee_is_redirected_to_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'employee@caturpilar.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('employee');

        $response = $this->post(route('login'), [
            'email' => 'employee@caturpilar.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    /**
     * DoD #2: Redirect setelah login sesuai role — Supervisor -> /dashboard.
     */
    public function test_supervisor_is_redirected_to_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'supervisor@caturpilar.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('supervisor');

        $response = $this->post(route('login'), [
            'email' => 'supervisor@caturpilar.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    /**
     * DoD #2: Redirect setelah login sesuai role — Quality -> /dashboard.
     */
    public function test_quality_is_redirected_to_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'quality@caturpilar.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('quality');

        $response = $this->post(route('login'), [
            'email' => 'quality@caturpilar.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    /**
     * DoD #2: Redirect setelah login sesuai role — Admin -> /admin (Filament).
     */
    public function test_admin_is_redirected_to_admin_panel_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@caturpilar.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('admin');

        $response = $this->post(route('login'), [
            'email' => 'admin@caturpilar.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/admin');
    }

    /**
     * DoD #1: Validasi form login — kredensial salah menghasilkan error.
     */
    public function test_login_with_invalid_credentials_returns_error(): void
    {
        $user = User::factory()->create([
            'email' => 'user@caturpilar.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'user@caturpilar.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }
}
