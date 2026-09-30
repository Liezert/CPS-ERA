<?php

namespace Tests\Feature;

use App\Enums\BaIncidentStatus;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use App\Services\BaIncidentService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard utama untuk semua role (keputusan owner 2026-09-27): supervisor/HR melihat antrean
 * laporan CAPA yang menunggu keputusannya, dengan kriteria yang sama seperti BaIncidentPolicy.
 */
class DashboardRolePanelTest extends TestCase
{
    use RefreshDatabase;

    private Division $produksi;

    private Division $engineering;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->produksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->engineering = Division::where('name', 'Engineering')->firstOrFail();
    }

    private function userWithRole(string $role, Division $division): User
    {
        $user = User::factory()->create(['division_id' => $division->id]);
        $user->assignRole($role);

        return $user;
    }

    private function report(Division $division, BaIncidentStatus $status, string $title): BaIncident
    {
        $reporter = $this->userWithRole('employee', $division);
        $incident = app(BaIncidentService::class)->saveDraft([
            'division_id' => $division->id,
            'title' => $title,
            'deskripsi_masalah' => 'Produk cacat ditemukan di akhir line.',
        ], $reporter);
        $incident->update(['status' => $status->value]);

        return $incident->fresh();
    }

    /**
     * HTML panel "Menunggu Review Anda" saja (dashboard juga punya daftar "Laporan CAPA Terbaru").
     */
    private function reviewPanel(User $user): string
    {
        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();
        $start = strpos($html, 'id="review-queue-title"');
        $this->assertNotFalse($start, 'Panel review tidak tampil.');

        return substr($html, $start, strpos($html, '</section>', $start) - $start);
    }

    public function test_supervisor_sees_only_own_division_reports_waiting_for_supervisor(): void
    {
        $supervisor = $this->userWithRole('supervisor', $this->produksi);
        $mine = $this->report($this->produksi, BaIncidentStatus::PendingSupervisor, 'Laporan Produksi Menunggu');
        $this->report($this->engineering, BaIncidentStatus::PendingSupervisor, 'Laporan Engineering Menunggu');
        $this->report($this->produksi, BaIncidentStatus::PendingHr, 'Laporan Produksi Sudah Di HR');

        $panel = $this->reviewPanel($supervisor);
        $this->assertStringContainsString('Laporan Produksi Menunggu', $panel);
        $this->assertStringContainsString(route('ba.show', $mine).'#panel-review', $panel);
        $this->assertStringNotContainsString('Laporan Engineering Menunggu', $panel);
        $this->assertStringNotContainsString('Laporan Produksi Sudah Di HR', $panel);

        $this->actingAs($supervisor)->get(route('dashboard'))->assertDontSee('Kelola Data (Admin)');
    }

    public function test_hr_sees_reports_waiting_for_hr_except_ones_they_approved_as_supervisor(): void
    {
        $hr = $this->userWithRole('quality', $this->engineering);
        $supervisorAndHr = $this->userWithRole('supervisor', $this->produksi);
        $supervisorAndHr->assignRole('quality');

        $this->report($this->engineering, BaIncidentStatus::PendingHr, 'Laporan Siap Keputusan HR');
        $ownApproval = $this->report($this->produksi, BaIncidentStatus::PendingSupervisor, 'Laporan Yang Disetujui Sendiri');
        app(BaIncidentService::class)->approveAsSupervisor($ownApproval, $supervisorAndHr);

        $hrPanel = $this->reviewPanel($hr);
        $this->assertStringContainsString('Laporan Siap Keputusan HR', $hrPanel);
        $this->assertStringContainsString('Laporan Yang Disetujui Sendiri', $hrPanel);

        // Four-eyes: yang sudah menyetujui di tahap Supervisor tidak melihatnya di antrean HR.
        $ownPanel = $this->reviewPanel($supervisorAndHr);
        $this->assertStringContainsString('Laporan Siap Keputusan HR', $ownPanel);
        $this->assertStringNotContainsString('Laporan Yang Disetujui Sendiri', $ownPanel);
    }

    public function test_employee_dashboard_has_no_role_panels(): void
    {
        $employee = $this->userWithRole('employee', $this->produksi);

        $this->actingAs($employee)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Menunggu Review Anda')
            ->assertDontSee('Kelola Data (Admin)');
    }
}
