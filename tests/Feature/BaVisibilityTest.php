<?php

namespace Tests\Feature;

use App\Enums\BaIncidentStatus;
use App\Livewire\Ba\Index as BaIndex;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use App\Services\BaIncidentService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Hak lihat laporan CAPA (BaIncident::scopeVisibleTo): daftar, dashboard, dan halaman detail
 * harus memakai aturan yang sama.
 */
class BaVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Division $produksi;

    private Division $engineering;

    private User $reporter;

    private User $colleague;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->produksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->engineering = Division::where('name', 'Engineering')->firstOrFail();

        $this->reporter = $this->userWithRole('employee', $this->produksi);
        $this->colleague = $this->userWithRole('employee', $this->produksi);
    }

    private function userWithRole(string $role, Division $division): User
    {
        $user = User::factory()->create(['division_id' => $division->id]);
        $user->assignRole($role);

        return $user;
    }

    private function report(User $creator, Division $division, BaIncidentStatus $status, string $title): BaIncident
    {
        $incident = app(BaIncidentService::class)->saveDraft([
            'division_id' => $division->id,
            'title' => $title,
            'deskripsi_masalah' => 'Produk cacat ditemukan di akhir line.',
        ], $creator);

        $incident->update(['status' => $status->value]);

        return $incident->fresh();
    }

    public function test_employee_sees_own_reports_and_submitted_division_reports_but_not_colleague_drafts(): void
    {
        $ownDraft = $this->report($this->reporter, $this->produksi, BaIncidentStatus::Draft, 'Draf Milik Sendiri');
        $colleagueDraft = $this->report($this->colleague, $this->produksi, BaIncidentStatus::Draft, 'Draf Rekan Kerja');
        $colleaguePending = $this->report($this->colleague, $this->produksi, BaIncidentStatus::PendingSupervisor, 'Laporan Rekan Terkirim');
        $otherDivision = $this->report($this->userWithRole('employee', $this->engineering), $this->engineering, BaIncidentStatus::Approved, 'Laporan Divisi Lain');

        Livewire::actingAs($this->reporter)
            ->test(BaIndex::class)
            ->assertSee('Draf Milik Sendiri')
            ->assertSee('Laporan Rekan Terkirim')
            ->assertDontSee('Draf Rekan Kerja')
            ->assertDontSee('Laporan Divisi Lain');

        $this->assertTrue($this->reporter->can('view', $ownDraft));
        $this->assertTrue($this->reporter->can('view', $colleaguePending));
        $this->assertFalse($this->reporter->can('view', $colleagueDraft));
        $this->assertFalse($this->reporter->can('view', $otherDivision));

        $this->actingAs($this->reporter)->get(route('ba.show', $colleagueDraft->id))->assertForbidden();
        $this->actingAs($this->reporter)->get(route('dashboard'))->assertDontSee('Draf Rekan Kerja');
    }

    public function test_supervisor_sees_every_status_in_own_division_and_hr_sees_all_divisions(): void
    {
        $colleagueDraft = $this->report($this->colleague, $this->produksi, BaIncidentStatus::Draft, 'Draf Rekan Kerja');
        $otherDivision = $this->report($this->userWithRole('employee', $this->engineering), $this->engineering, BaIncidentStatus::Approved, 'Laporan Divisi Lain');

        $supervisor = $this->userWithRole('supervisor', $this->produksi);
        $this->assertTrue($supervisor->can('view', $colleagueDraft));
        $this->assertFalse($supervisor->can('view', $otherDivision));

        foreach (['quality', 'admin'] as $role) {
            $hr = $this->userWithRole($role, $this->engineering);
            $this->assertTrue($hr->can('view', $colleagueDraft), $role);
            $this->assertTrue($hr->can('view', $otherDivision), $role);
        }
    }

    public function test_status_filter_offers_every_approval_status(): void
    {
        $pending = $this->report($this->reporter, $this->produksi, BaIncidentStatus::PendingHr, 'Laporan Menunggu HR');
        $this->report($this->reporter, $this->produksi, BaIncidentStatus::Approved, 'Laporan Sudah Disetujui');

        Livewire::actingAs($this->reporter)
            ->test(BaIndex::class)
            ->assertSeeHtml('value="pending_supervisor"')
            ->assertSeeHtml('value="revision_requested"')
            ->assertDontSeeHtml('value="submitted"')
            ->set('selectedStatus', $pending->status)
            ->assertSee('Laporan Menunggu HR')
            ->assertDontSee('Laporan Sudah Disetujui');
    }
}
