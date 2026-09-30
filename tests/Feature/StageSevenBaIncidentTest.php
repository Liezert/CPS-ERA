<?php

namespace Tests\Feature;

use App\Livewire\Ba\Create as BaCreate;
use App\Livewire\Ba\Show as BaShow;
use App\Models\BaActivityLog;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use App\Services\BaIncidentService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StageSevenBaIncidentTest extends TestCase
{
    use RefreshDatabase;

    protected Division $divisionProduksi;

    protected Division $divisionEngineering;

    protected User $employeeProduksi;

    protected User $supervisorProduksi;

    protected User $supervisorEngineering;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->divisionProduksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->divisionEngineering = Division::where('name', 'Engineering')->firstOrFail();

        $this->employeeProduksi = User::factory()->create([
            'name' => 'Budi Santoso',
            'division_id' => $this->divisionProduksi->id,
            'employee_id' => 'CPS-00101',
        ]);
        $this->employeeProduksi->assignRole('employee');

        $this->supervisorProduksi = User::factory()->create([
            'name' => 'Pak Joko SPV Produksi',
            'division_id' => $this->divisionProduksi->id,
            'employee_id' => 'CPS-00010',
        ]);
        $this->supervisorProduksi->assignRole('supervisor');

        $this->supervisorEngineering = User::factory()->create([
            'name' => 'Pak Hendra SPV Engineering',
            'division_id' => $this->divisionEngineering->id,
            'employee_id' => 'CPS-00020',
        ]);
        $this->supervisorEngineering->assignRole('supervisor');
    }

    /**
     * DoD #1: Nomor BA auto-generate, tidak bisa diedit manual (read-only, IBM Plex Mono).
     */
    public function test_nomor_ba_is_auto_generated_and_rendered_read_only_in_mono_font(): void
    {
        $response = $this->actingAs($this->employeeProduksi)->get(route('ba.create'));
        $response->assertStatus(200);

        // Input nomor_ba harus readonly, disabled, dan menggunakan font-mono
        $response->assertSee('font-mono', false);
        $response->assertSee('readonly', false);
        $response->assertSee('disabled', false);
        $response->assertSee('BA-'.date('Y').'-');

        Livewire::actingAs($this->employeeProduksi)
            ->test(BaCreate::class)
            ->assertSeeHtml('readonly')
            ->assertSeeHtml('disabled')
            ->assertSeeHtml('font-mono');
    }

    /**
     * Pilihan Divisi: 11 divisi pelapor final (Marketing & Sales digabung). HRGA adalah role approval, bukan divisi pelapor.
     */
    public function test_form_contains_11_final_divisions_without_hrga(): void
    {
        $expectedDivisions = [
            'Engineering',
            'Finance Accounting Tax',
            'Jahit',
            'Marketing',
            'Sales',
            'PPIC',
            'Plant Balben & Krian',
            'Produksi',
            'Purchasing',
            'Quality Control',
            'RM Warehouse',
            'Warehouse & Delivery',
        ];

        $response = $this->actingAs($this->employeeProduksi)->get(route('ba.create'));
        $response->assertStatus(200);

        foreach ($expectedDivisions as $divName) {
            $response->assertSee($divName);
        }

        $response->assertDontSee('>HRGA</option>', false);
    }

    /**
     * DoD #2 (diperbarui 2026-09-27): Form Digital CAPA terstruktur dikirim langsung ke Supervisor,
     * tanpa langkah video (video kini kontribusi terpisah di Learning).
     */
    public function test_structured_capa_form_is_submitted_straight_to_supervisor(): void
    {
        Livewire::actingAs($this->employeeProduksi)
            ->test(BaCreate::class)
            ->set('divisionId', $this->divisionProduksi->id)
            ->set('tanggalMasalah', '2026-09-08')
            ->set('lokasi', 'Lini Injeksi Moulding 03')
            ->set('sumberKetidaksesuaian', 'laporan_ketidaksesuaian')
            ->set('deskripsiMasalah', 'Piston hidrolik mesin cetak bocor pada siklus produksi ke-400.')
            ->set('why1', 'Seal aus karena panas berlebih.')
            ->set('kesimpulanAkarMasalah', 'Seal hidrolik aus.')
            ->set('koreksiDeskripsi', 'Ganti seal darurat.')
            ->set('korektifDeskripsi', 'Pasang sensor temperatur oli.')
            ->call('submitReport')
            ->assertHasNoErrors();

        $incident = BaIncident::where('lokasi', 'Lini Injeksi Moulding 03')->first();
        $this->assertNotNull($incident);
        $this->assertSame('pending_supervisor', $incident->status);

        // Verifikasi activity log penyerahan BA dibuat
        $this->assertDatabaseHas('ba_activity_logs', [
            'ba_incident_id' => $incident->id,
            'actor_id' => $this->employeeProduksi->id,
            'action' => 'BA Diserahkan',
        ]);
    }

    /**
     * DoD #3: Status HANYA 3 kemungkinan, sesuai urutan Created -> Reviewed -> Closed.
     */
    public function test_status_has_only_three_strict_possibilities_in_sequence(): void
    {
        $incident = BaIncident::create([
            'nomor_ba' => 'BA-2026-0001',
            'title' => 'Insiden Overheat Chiller',
            'division_id' => $this->divisionProduksi->id,
            'file_ba_url' => '/storage/ba.pdf',
            'file_ftk_url' => '/storage/ftk.pdf',
            'status' => 'created',
            'created_by' => $this->employeeProduksi->id,
        ]);

        // 1. Status 'created'
        $this->assertSame('created', $incident->status);
        $responseCreated = $this->actingAs($this->employeeProduksi)->get(route('ba.show', $incident->id));
        $responseCreated->assertSee('Created');
        $responseCreated->assertDontSee('Reviewed');
        $responseCreated->assertDontSee('Closed');

        // 2. Transisi ke 'reviewed'
        $incident->update([
            'status' => 'reviewed',
            'reviewed_by' => $this->supervisorProduksi->id,
            'reviewed_at' => now(),
        ]);
        $responseReviewed = $this->actingAs($this->employeeProduksi)->get(route('ba.show', $incident->id));
        $responseReviewed->assertSee('Reviewed');
        $responseReviewed->assertDontSee('Created');

        // 3. Transisi ke 'closed'
        $incident->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
        $responseClosed = $this->actingAs($this->employeeProduksi)->get(route('ba.show', $incident->id));
        $responseClosed->assertSee('Closed');
        $responseClosed->assertDontSee('Created');
    }

    /**
     * DoD #4 (diperbarui 2026-09-27): approve/reject di halaman preview laporan, dan tahap
     * Supervisor hanya untuk Supervisor dari divisi yang sama dengan BA tersebut.
     */
    public function test_approval_in_preview_is_gated_to_supervisor_of_same_division(): void
    {
        $incident = BaIncident::create([
            'nomor_ba' => 'BA-2026-0099',
            'title' => 'Patahan Baut Moulding Produksi',
            'description' => 'Baut penahan moulding patah saat penggantian cetakan.',
            'division_id' => $this->divisionProduksi->id, // Divisi Produksi
            'file_ba_url' => '/storage/ba.pdf',
            'file_ftk_url' => '/storage/ftk.pdf',
            'status' => 'pending_supervisor',
            'created_by' => $this->employeeProduksi->id,
        ]);
        // 1. Employee (meskipun divisi sama) -> tidak bisa review, tidak ada panel review
        $this->assertFalse($this->employeeProduksi->can('reviewAsSupervisor', $incident));
        Livewire::actingAs($this->employeeProduksi)
            ->test(BaShow::class, ['incident' => $incident])
            ->assertDontSee('Keputusan Review Laporan');

        // 2. Supervisor Divisi Lain (Engineering) -> tidak bisa review
        $this->assertFalse($this->supervisorEngineering->can('reviewAsSupervisor', $incident));

        // 3. Supervisor Divisi Sama (Produksi) -> panel review muncul di halaman preview
        $this->assertTrue($this->supervisorProduksi->can('reviewAsSupervisor', $incident));
        Livewire::actingAs($this->supervisorProduksi)
            ->test(BaShow::class, ['incident' => $incident])
            ->assertSee('Keputusan Review Laporan')
            ->assertSee('Setujui & Teruskan ke HR')
            ->assertSee('Tolak & Minta Revisi');

        // Tahap Supervisor meneruskan ke HR, lalu tim HR (quality) menyetujui final.
        $service = app(BaIncidentService::class);
        $service->approveAsSupervisor($incident, $this->supervisorProduksi, 'Baut pengganti sudah terpasang.');

        $quality = User::factory()->create(['division_id' => $this->divisionEngineering->id]);
        $quality->assignRole('quality');
        $service->approve($incident->fresh(), $quality, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => 'Verifikasi tindakan korektif diverifikasi efektif.',
        ]);

        // Verifikasi status BA berubah menjadi 'approved'
        $incident->refresh();
        $this->assertSame('approved', $incident->status);
        $this->assertSame($this->supervisorProduksi->id, $incident->supervisor_reviewed_by);
        $this->assertSame($quality->id, $incident->reviewed_by);

        // Laporan CAPA selesai di modulnya sendiri: tidak masuk Learning maupun Knowledge Repository.
        $this->assertDatabaseMissing('knowledge_documents', ['source_ba_id' => $incident->id]);
        $this->assertDatabaseMissing('learning_materials', ['source_ba_id' => $incident->id]);

        // Verifikasi log timeline riwayat bertambah
        $this->assertDatabaseHas('ba_activity_logs', [
            'ba_incident_id' => $incident->id,
            'actor_id' => $this->supervisorProduksi->id,
            'action' => 'BA Disetujui Supervisor',
        ]);
    }

    /**
     * Timeline Update History menampilkan garis vertikal tipis dan titik. Timeline bersifat internal
     * (Keputusan poin 28): tampil untuk reviewer, tidak untuk karyawan pelapor.
     */
    public function test_update_history_timeline_renders_logs_with_timestamps_and_notes(): void
    {
        $incident = BaIncident::create([
            'nomor_ba' => 'BA-2026-0077',
            'title' => 'Insiden Sensor Tekanan Error',
            'division_id' => $this->divisionProduksi->id,
            'file_ba_url' => '/storage/ba.pdf',
            'file_ftk_url' => '/storage/ftk.pdf',
            'status' => 'created',
            'created_by' => $this->employeeProduksi->id,
        ]);

        BaActivityLog::create([
            'ba_incident_id' => $incident->id,
            'actor_id' => $this->employeeProduksi->id,
            'action' => 'Laporan BA diterbitkan',
            'note' => 'Dokumen fisik telah dikonfirmasi oleh shift malam.',
        ]);

        $response = $this->actingAs($this->supervisorProduksi)->get(route('ba.show', $incident->id));
        $response->assertStatus(200);

        $response->assertSee('Laporan BA diterbitkan');
        $response->assertSee('Dokumen fisik telah dikonfirmasi oleh shift malam.');
        $response->assertSee('Update History (Riwayat Aktivitas)');

        $this->actingAs($this->employeeProduksi)->get(route('ba.show', $incident->id))
            ->assertOk()
            ->assertDontSee('Update History (Riwayat Aktivitas)')
            ->assertDontSee('Dokumen fisik telah dikonfirmasi oleh shift malam.');
    }
}
