<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_employee_is_denied_from_filament_admin_panel(): void
    {
        $division = Division::where('name', 'Engineering')->firstOrFail();
        $employee = User::factory()->create([
            'division_id' => $division->id,
            'employee_id' => 'CPS-00200',
        ]);
        $employee->assignRole('employee');

        $panel = Filament::getPanel('admin');
        $this->assertFalse($employee->canAccessPanel($panel));

        $response = $this->actingAs($employee)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_admin_supervisor_and_quality_can_access_filament_admin_panel(): void
    {
        $panel = Filament::getPanel('admin');

        foreach (['admin', 'supervisor', 'quality'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->assertTrue(
                $user->canAccessPanel($panel),
                "Role {$role} should be able to access the admin panel."
            );

            $response = $this->actingAs($user)->get('/admin');
            $response->assertSuccessful();
        }
    }

    public function test_gates_evaluate_properly_for_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $quality = User::factory()->create();
        $quality->assignRole('quality');

        $employee = User::factory()->create();
        $employee->assignRole('employee');

        // Admin passes all gates (including gate::before)
        $this->assertTrue(Gate::forUser($admin)->allows('admin'));
        $this->assertTrue(Gate::forUser($admin)->allows('access-admin-panel'));

        // Supervisor
        $this->assertTrue(Gate::forUser($supervisor)->allows('supervisor'));
        $this->assertTrue(Gate::forUser($supervisor)->allows('access-admin-panel'));
        $this->assertFalse(Gate::forUser($supervisor)->allows('admin'));

        // Quality
        $this->assertTrue(Gate::forUser($quality)->allows('quality'));
        $this->assertTrue(Gate::forUser($quality)->allows('access-admin-panel'));
        $this->assertFalse(Gate::forUser($quality)->allows('admin'));

        // Employee
        $this->assertTrue(Gate::forUser($employee)->allows('employee'));
        $this->assertFalse(Gate::forUser($employee)->allows('admin'));
        $this->assertFalse(Gate::forUser($employee)->allows('access-admin-panel'));
    }
}
