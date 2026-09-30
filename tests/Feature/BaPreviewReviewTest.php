<?php

namespace Tests\Feature;

use App\Enums\BaIncidentStatus;
use App\Livewire\Ba\Show as BaShow;
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
 * Keputusan owner 2026-09-27: Supervisor (tahap 1) dan HR (tahap 2) menyetujui/menolak laporan CAPA
 * langsung dari halaman preview, tanpa harus membuka panel admin. Aturan tahap tetap dari policy.
 */
class BaPreviewReviewTest extends TestCase
{
    use RefreshDatabase;

    private Division $produksi;

    private User $reporter;

    private User $supervisorProduksi;

    private User $supervisorEngineering;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->produksi = Division::where('name', 'Produksi')->firstOrFail();
        $engineering = Division::where('name', 'Engineering')->firstOrFail();

        $this->reporter = $this->userWithRole('employee', $this->produksi);
        $this->supervisorProduksi = $this->userWithRole('supervisor', $this->produksi);
        $this->supervisorEngineering = $this->userWithRole('supervisor', $engineering);
        $this->hr = $this->userWithRole('quality', $engineering);
    }

    private function userWithRole(string $role, Division $division): User
    {
        $user = User::factory()->create(['division_id' => $division->id]);
        $user->assignRole($role);

        return $user;
    }

    private function report(BaIncidentStatus $status): BaIncident
    {
        $incident = app(BaIncidentService::class)->saveDraft([
            'division_id' => $this->produksi->id,
            'deskripsi_masalah' => 'Produk cacat ditemukan di akhir line.',
        ], $this->reporter);

        $incident->update(['status' => $status->value]);

        return $incident->fresh();
    }

    public function test_supervisor_of_the_division_approves_from_preview_and_report_moves_to_hr(): void
    {
        $incident = $this->report(BaIncidentStatus::PendingSupervisor);

        Livewire::actingAs($this->supervisorProduksi)
            ->test(BaShow::class, ['incident' => $incident])
            ->assertSee('Tahap 1 — Review Supervisor')
            ->assertSee('Setujui & Teruskan ke HR')
            ->assertDontSee('Review di Panel Admin')
            ->set('catatanSupervisor', 'Sudah dicek langsung di line.')
            ->call('approve')
            ->assertHasNoErrors()
            ->assertRedirect(route('ba.show', $incident));

        $incident->refresh();
        $this->assertSame(BaIncidentStatus::PendingHr->value, $incident->status);
        $this->assertSame($this->supervisorProduksi->id, $incident->supervisor_reviewed_by);
    }

    public function test_supervisor_rejection_from_preview_requires_a_reason_and_requests_revision(): void
    {
        $incident = $this->report(BaIncidentStatus::PendingSupervisor);

        $component = Livewire::actingAs($this->supervisorProduksi)
            ->test(BaShow::class, ['incident' => $incident])
            ->call('reject')
            ->assertHasErrors(['catatanPenolakan' => 'required']);

        $this->assertSame(BaIncidentStatus::PendingSupervisor->value, $incident->fresh()->status);

        $component->set('catatanPenolakan', 'Analisa why ke-2 belum didukung data.')
            ->call('reject')
            ->assertHasNoErrors();

        $incident->refresh();
        $this->assertSame(BaIncidentStatus::RevisionRequested->value, $incident->status);
        $this->assertSame('Analisa why ke-2 belum didukung data.', $incident->catatan_penolakan);
    }

    public function test_hr_approves_final_from_preview_with_verification(): void
    {
        $incident = $this->report(BaIncidentStatus::PendingSupervisor);
        app(BaIncidentService::class)->approveAsSupervisor($incident, $this->supervisorProduksi);

        $component = Livewire::actingAs($this->hr)
            ->test(BaShow::class, ['incident' => $incident->fresh()])
            ->assertSee('Tahap 2 — Review HR')
            ->assertSee('Setujui Final')
            ->set('statusVerifikasi', 'efektif')
            ->set('buktiObjektif', '')
            ->call('approve')
            ->assertHasErrors(['buktiObjektif' => 'required_if']);

        $this->assertSame(BaIncidentStatus::PendingHr->value, $incident->fresh()->status);

        $component->set('buktiObjektif', 'Tidak ada cacat selama 2 minggu.')
            ->call('approve')
            ->assertHasNoErrors();

        $incident->refresh();
        $this->assertSame(BaIncidentStatus::Approved->value, $incident->status);
        $this->assertSame('efektif', $incident->status_verifikasi);
        $this->assertSame($this->hr->id, $incident->reviewed_by);
    }

    public function test_hr_rejects_permanently_from_preview(): void
    {
        $incident = $this->report(BaIncidentStatus::PendingSupervisor);
        app(BaIncidentService::class)->approveAsSupervisor($incident, $this->supervisorProduksi);

        Livewire::actingAs($this->hr)
            ->test(BaShow::class, ['incident' => $incident->fresh()])
            ->set('catatanPenolakan', 'Tindakan korektif tidak menyentuh akar masalah.')
            ->call('reject')
            ->assertHasNoErrors();

        $this->assertSame(BaIncidentStatus::Rejected->value, $incident->fresh()->status);
    }

    public function test_people_outside_the_active_stage_see_no_review_panel_and_cannot_act(): void
    {
        $incident = $this->report(BaIncidentStatus::PendingSupervisor);

        // Pelapor sendiri dan HR (belum tahapnya) tidak melihat panel review.
        foreach ([$this->reporter, $this->hr] as $user) {
            Livewire::actingAs($user)
                ->test(BaShow::class, ['incident' => $incident])
                ->assertDontSee('Keputusan Review Laporan')
                ->call('approve')
                ->assertForbidden();
        }

        $this->assertSame(BaIncidentStatus::PendingSupervisor->value, $incident->fresh()->status);
    }

    public function test_supervisor_of_another_division_cannot_open_or_review_the_report(): void
    {
        $incident = $this->report(BaIncidentStatus::PendingSupervisor);

        $this->actingAs($this->supervisorEngineering)
            ->get(route('ba.show', $incident))
            ->assertForbidden();

        $this->assertSame(BaIncidentStatus::PendingSupervisor->value, $incident->fresh()->status);
    }

    public function test_supervisor_who_approved_cannot_also_decide_the_hr_stage(): void
    {
        // User dengan role supervisor + quality: four-eyes tetap berlaku di halaman preview.
        $this->supervisorProduksi->assignRole('quality');
        $incident = $this->report(BaIncidentStatus::PendingSupervisor);
        app(BaIncidentService::class)->approveAsSupervisor($incident, $this->supervisorProduksi);

        Livewire::actingAs($this->supervisorProduksi)
            ->test(BaShow::class, ['incident' => $incident->fresh()])
            ->assertDontSee('Keputusan Review Laporan')
            ->set('buktiObjektif', 'Menyetujui laporan sendiri.')
            ->call('approve')
            ->assertForbidden();

        $this->assertSame(BaIncidentStatus::PendingHr->value, $incident->fresh()->status);
    }
}
