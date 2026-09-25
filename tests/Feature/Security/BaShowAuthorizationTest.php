<?php

namespace Tests\Feature\Security;

use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit pre-launch: halaman detail CAPA wajib lolos BaIncidentPolicy::view
 * (admin/quality semua divisi, supervisor divisinya, employee: miliknya atau divisinya).
 */
class BaShowAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Division $produksi;

    private Division $engineering;

    private BaIncident $incident;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->produksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->engineering = Division::where('name', 'Engineering')->firstOrFail();
        $this->owner = $this->userWithRole('employee', $this->produksi);

        $this->incident = BaIncident::create([
            'nomor_ba' => 'BA-2026-0501',
            'title' => 'Laporan rahasia divisi Produksi',
            'division_id' => $this->produksi->id,
            'deskripsi_masalah' => 'Isi laporan internal divisi Produksi.',
            'status' => 'pending_supervisor',
            'created_by' => $this->owner->id,
        ]);
    }

    private function userWithRole(string $role, Division $division): User
    {
        $user = User::factory()->create(['division_id' => $division->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_employee_from_another_division_is_forbidden(): void
    {
        $outsider = $this->userWithRole('employee', $this->engineering);

        $this->actingAs($outsider)->get(route('ba.show', $this->incident))->assertForbidden();
    }

    public function test_supervisor_from_another_division_is_forbidden(): void
    {
        $outsider = $this->userWithRole('supervisor', $this->engineering);

        $this->actingAs($outsider)->get(route('ba.show', $this->incident))->assertForbidden();
    }

    public function test_users_allowed_by_the_policy_can_open_the_report(): void
    {
        $allowed = [
            'pemilik' => $this->owner,
            'employee sedivisi' => $this->userWithRole('employee', $this->produksi),
            'supervisor sedivisi' => $this->userWithRole('supervisor', $this->produksi),
            'admin' => $this->userWithRole('admin', $this->engineering),
            'quality' => $this->userWithRole('quality', $this->engineering),
        ];

        foreach ($allowed as $label => $user) {
            $this->actingAs($user)->get(route('ba.show', $this->incident))
                ->assertOk()
                ->assertSee($this->incident->nomor_ba);
        }
    }
}
