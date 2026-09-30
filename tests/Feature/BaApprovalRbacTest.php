<?php

namespace Tests\Feature;

use App\Enums\BaIncidentStatus;
use App\Filament\Resources\BaIncidents\Pages\ListBaIncidents;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use App\Services\BaIncidentService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Stage 3 alur approval CAPA: otorisasi approve/reject per tahap dan tab antrean di panel Filament.
 * Approve/tolak dari halaman preview diuji di BaPreviewReviewTest.
 */
class BaApprovalRbacTest extends TestCase
{
    use RefreshDatabase;

    private Division $produksi;

    private Division $engineering;

    private User $reporter;

    private User $supervisorProduksi;

    private User $supervisorEngineering;

    private User $quality;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->produksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->engineering = Division::where('name', 'Engineering')->firstOrFail();

        $this->reporter = $this->userWithRole('employee', $this->produksi);
        $this->supervisorProduksi = $this->userWithRole('supervisor', $this->produksi);
        $this->supervisorEngineering = $this->userWithRole('supervisor', $this->engineering);
        $this->quality = $this->userWithRole('quality', $this->engineering);
        $this->admin = $this->userWithRole('admin', $this->engineering);
    }

    private function userWithRole(string $role, Division $division): User
    {
        $user = User::factory()->create(['division_id' => $division->id]);
        $user->assignRole($role);

        return $user;
    }

    private function report(BaIncidentStatus $status, Division $division): BaIncident
    {
        $incident = app(BaIncidentService::class)->saveDraft([
            'division_id' => $division->id,
            'deskripsi_masalah' => 'Produk cacat ditemukan di akhir line.',
        ], $this->reporter);

        $incident->update(['status' => $status->value]);

        return $incident->fresh();
    }

    public function test_supervisor_stage_is_limited_to_own_division_supervisor_while_pending_supervisor(): void
    {
        $pending = $this->report(BaIncidentStatus::PendingSupervisor, $this->produksi);

        $this->assertTrue($this->supervisorProduksi->can('reviewAsSupervisor', $pending));
        $this->assertFalse($this->supervisorEngineering->can('reviewAsSupervisor', $pending));
        $this->assertFalse($this->reporter->can('reviewAsSupervisor', $pending));
        $this->assertFalse($this->quality->can('reviewAsSupervisor', $pending));
        $this->assertFalse($this->admin->can('reviewAsSupervisor', $pending));

        foreach ([BaIncidentStatus::Draft, BaIncidentStatus::RevisionRequested, BaIncidentStatus::PendingHr, BaIncidentStatus::Approved] as $status) {
            $this->assertFalse(
                $this->supervisorProduksi->can('reviewAsSupervisor', $this->report($status, $this->produksi)),
                "Supervisor tidak boleh review laporan berstatus {$status->value}."
            );
        }
    }

    public function test_hr_stage_is_for_quality_and_admin_across_divisions_while_pending_hr(): void
    {
        $pendingHrProduksi = $this->report(BaIncidentStatus::PendingHr, $this->produksi);

        $this->assertTrue($this->quality->can('reviewAsHr', $pendingHrProduksi));
        $this->assertTrue($this->admin->can('reviewAsHr', $pendingHrProduksi));
        $this->assertFalse($this->supervisorProduksi->can('reviewAsHr', $pendingHrProduksi));
        $this->assertFalse($this->reporter->can('reviewAsHr', $pendingHrProduksi));

        foreach ([BaIncidentStatus::PendingSupervisor, BaIncidentStatus::RevisionRequested, BaIncidentStatus::Approved, BaIncidentStatus::Rejected] as $status) {
            $this->assertFalse(
                $this->quality->can('reviewAsHr', $this->report($status, $this->produksi)),
                "HR tidak boleh review laporan berstatus {$status->value}."
            );
        }
    }

    public function test_supervisor_list_defaults_to_own_division_queue_and_keeps_other_statuses_under_all(): void
    {
        $queue = $this->report(BaIncidentStatus::PendingSupervisor, $this->produksi);
        $alreadyApproved = $this->report(BaIncidentStatus::PendingHr, $this->produksi);
        $otherDivision = $this->report(BaIncidentStatus::PendingSupervisor, $this->engineering);

        Livewire::actingAs($this->supervisorProduksi)
            ->test(ListBaIncidents::class)
            ->assertSet('activeTab', 'pending_supervisor')
            ->assertCanSeeTableRecords([$queue])
            ->assertCanNotSeeTableRecords([$alreadyApproved, $otherDivision])
            ->set('activeTab', 'all')
            ->assertCanSeeTableRecords([$queue, $alreadyApproved])
            ->assertCanNotSeeTableRecords([$otherDivision]);
    }

    public function test_hr_list_defaults_to_cross_division_hr_queue(): void
    {
        $produksi = $this->report(BaIncidentStatus::PendingHr, $this->produksi);
        $engineering = $this->report(BaIncidentStatus::PendingHr, $this->engineering);
        $stillAtSupervisor = $this->report(BaIncidentStatus::PendingSupervisor, $this->produksi);

        foreach ([$this->quality, $this->admin] as $hrUser) {
            Livewire::actingAs($hrUser)
                ->test(ListBaIncidents::class)
                ->assertSet('activeTab', 'pending_hr')
                ->assertCanSeeTableRecords([$produksi, $engineering])
                ->assertCanNotSeeTableRecords([$stillAtSupervisor])
                ->set('activeTab', 'all')
                ->assertCanSeeTableRecords([$produksi, $engineering, $stillAtSupervisor]);
        }
    }
}
