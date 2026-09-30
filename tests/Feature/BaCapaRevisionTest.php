<?php

namespace Tests\Feature;

use App\Livewire\Ba\Create as BaCreate;
use App\Livewire\Ba\Show as BaShow;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use App\Models\Video;
use App\Services\BaIncidentService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BaCapaRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected Division $divisionProduksi;

    protected Division $divisionEngineering;

    protected User $employeeProduksi;

    protected User $supervisorProduksi;

    protected User $supervisorEngineering;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->divisionProduksi = Division::where('name', 'Produksi')->firstOrFail();
        $this->divisionEngineering = Division::where('name', 'Engineering')->firstOrFail();

        $this->employeeProduksi = User::factory()->create([
            'name' => 'Budi Operator',
            'division_id' => $this->divisionProduksi->id,
            'employee_id' => 'EMP-001',
        ]);
        $this->employeeProduksi->assignRole('employee');

        $this->supervisorProduksi = User::factory()->create([
            'name' => 'Joko SPV Produksi',
            'division_id' => $this->divisionProduksi->id,
            'employee_id' => 'SPV-001',
        ]);
        $this->supervisorProduksi->assignRole('supervisor');

        $this->supervisorEngineering = User::factory()->create([
            'name' => 'Hendra SPV Engineering',
            'division_id' => $this->divisionEngineering->id,
            'employee_id' => 'SPV-002',
        ]);
        $this->supervisorEngineering->assignRole('supervisor');

        $this->admin = User::factory()->create([
            'name' => 'Super Admin',
            'division_id' => $this->divisionProduksi->id,
            'employee_id' => 'ADM-001',
        ]);
        $this->admin->assignRole('admin');
    }

    /**
     * TEST 1 (diperbarui 2026-09-27): laporan CAPA tidak lagi melampirkan video. Formulir yang belum
     * lengkap tidak bisa dikirim; formulir lengkap langsung terkirim ke Supervisor tanpa video.
     */
    public function test_report_is_sent_to_supervisor_only_when_the_form_is_complete_and_needs_no_video(): void
    {
        $lw = Livewire::actingAs($this->employeeProduksi)
            ->test(BaCreate::class)
            ->set('divisionId', $this->divisionProduksi->id)
            ->set('tanggalMasalah', '2026-09-10')
            ->set('lokasi', 'Lini Injeksi Moulding 03')
            ->set('sumberKetidaksesuaian', 'audit')
            ->set('deskripsiMasalah', 'Ditemukan kebocoran oli pelumas pada piston utama.')
            ->call('submitReport')
            ->assertHasErrors(['why1', 'kesimpulanAkarMasalah', 'koreksiDeskripsi', 'korektifDeskripsi']);

        $this->assertDatabaseCount('ba_incidents', 0);

        $lw->set('why1', 'Seal hidrolik mengalami keretakan mikro.')
            ->set('kesimpulanAkarMasalah', 'Degradasi material seal akibat temperatur berlebih.')
            ->set('koreksiDeskripsi', 'Penggantian seal darurat dan pembersihan tumpahan oli.')
            ->set('korektifDeskripsi', 'Pemasangan sensor temperatur otomatis pada pompa sirkulasi.')
            ->call('submitReport')
            ->assertHasNoErrors()
            ->assertDispatched('capa-draft-submitted')
            ->assertRedirect(route('ba.index'));

        $incident = BaIncident::findOrFail($lw->get('baIncidentId'));
        $this->assertSame('pending_supervisor', $incident->status);
        $this->assertNull($incident->video);
    }

    /**
     * TEST 2: Seluruh isian form tersimpan di database, baik lewat "Simpan Draf" maupun saat dikirim.
     */
    public function test_form_data_is_saved_to_the_database_as_draft_and_on_submit(): void
    {
        $lw = Livewire::actingAs($this->employeeProduksi)
            ->test(BaCreate::class)
            ->set('divisionId', $this->divisionProduksi->id)
            ->set('tanggalMasalah', '2026-09-08')
            ->set('lokasi', 'Warehouse Rack A-12')
            ->set('sumberKetidaksesuaian', 'keluhan_pelanggan')
            ->set('deskripsiMasalah', 'Pallet carton pecah saat proses pemindahan forklift.')
            ->set('why1', 'Kapasitas angkut melebihi batas beban maksimum.')
            ->set('why2', 'Operator forklift tidak membaca label kapasitas.')
            ->set('kesimpulanAkarMasalah', 'Kurangnya rambu visual batas kapasitas beban pada forklift.')
            ->set('koreksiDeskripsi', 'Sortir dan repacking kardus yang rusak.')
            ->set('koreksiPic', 'Budi Warehouse')
            ->set('koreksiWaktu', '1 Hari')
            ->set('korektifDeskripsi', 'Pemasangan stiker penanda beban dan briefing ulang SOP.')
            ->set('korektifPic', 'SPV Logistik')
            ->set('korektifWaktu', '2026-10-03')
            ->set('isPotensiRisiko', true)
            ->set('isPotensiPeluang', false)
            ->call('saveDraftOnly')
            ->assertHasNoErrors();

        $incident = BaIncident::findOrFail($lw->get('baIncidentId'));
        $this->assertSame('draft', $incident->status);
        $this->assertSame('Warehouse Rack A-12', $incident->lokasi);
        $this->assertSame('keluhan_pelanggan', $incident->sumber_ketidaksesuaian);
        $this->assertSame('Kapasitas angkut melebihi batas beban maksimum.', $incident->why_1);
        $this->assertSame('Budi Warehouse', $incident->koreksi_pic);
        $this->assertTrue($incident->is_potensi_risiko);

        // Ubah sedikit lalu kirim: perubahan ikut tersimpan pada draf yang sama.
        $lw->set('lokasi', 'Warehouse Rack B-05')
            ->call('submitReport')
            ->assertHasNoErrors();

        $incident->refresh();
        $this->assertSame('Warehouse Rack B-05', $incident->lokasi);
        $this->assertSame('pending_supervisor', $incident->status);
        $this->assertSame(1, BaIncident::count());
    }

    /**
     * TEST 3: Approve wajib mengisi status_verifikasi, tidak bisa approve tanpa itu.
     */
    public function test_approve_requires_status_verifikasi_and_cannot_approve_without_it(): void
    {
        $service = app(BaIncidentService::class);

        // Buat draft lalu kirim ke Supervisor
        $incident = $service->saveDraft([
            'division_id' => $this->divisionProduksi->id,
            'tanggal_masalah' => '2026-09-05',
            'lokasi' => 'Mesin CNC 01',
            'sumberKetidaksesuaian' => 'internal_audit',
            'deskripsi_masalah' => 'Sensor spindle macet.',
            'why_1' => 'Gram serbuk besi masuk ke celah casing.',
            'kesimpulan_akar_masalah' => 'Filter penahan gram robek.',
            'koreksi_deskripsi' => 'Bersihkan celah sensor dan ganti filter.',
            'korektif_deskripsi' => 'Jadwalkan pembersihan sensor mingguan.',
        ], $this->employeeProduksi);

        $service->submit($incident, $this->employeeProduksi);

        $incident->refresh();
        $this->assertSame('pending_supervisor', $incident->status);

        // Tahap Supervisor meneruskan ke HR; evaluasi formal diisi HR di tahap final.
        $service->approveAsSupervisor($incident, $this->supervisorProduksi, 'Sensor sudah dicek langsung di lapangan.');
        $incident->refresh();
        $this->assertSame('pending_hr', $incident->status);

        // 1. Coba approve tanpa status_verifikasi
        try {
            $service->approve($incident, $this->admin, [
                'status_verifikasi' => null,
            ]);
            $this->fail('Harus melempar DomainException jika status_verifikasi kosong.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Status verifikasi', $e->getMessage());
        }

        // 2. Coba status_verifikasi = efektif tanpa bukti_objektif
        try {
            $service->approve($incident, $this->admin, [
                'status_verifikasi' => 'efektif',
                'bukti_objektif' => '',
            ]);
            $this->fail('Harus melempar DomainException jika status efektif tidak menyertakan bukti_objektif.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Bukti objektif', $e->getMessage());
        }

        // 3. Coba status_verifikasi = tidak_efektif tanpa alasan_tidak_efektif
        try {
            $service->approve($incident, $this->admin, [
                'status_verifikasi' => 'tidak_efektif',
                'alasan_tidak_efektif' => '',
            ]);
            $this->fail('Harus melempar DomainException jika status tidak efektif tidak menyertakan alasan.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Alasan ketidakefektifan', $e->getMessage());
        }

        // 4. Approve dengan data verifikasi lengkap
        $approved = $service->approve($incident, $this->admin, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => 'Sensor telah diuji 24 jam nonstop tanpa alarm kegagalan.',
        ]);

        $this->assertSame('approved', $approved->status);
        $this->assertSame('efektif', $approved->status_verifikasi);
        $this->assertSame('Sensor telah diuji 24 jam nonstop tanpa alarm kegagalan.', $approved->bukti_objektif);
        $this->assertSame($this->admin->id, $approved->reviewed_by);
        $this->assertNotNull($approved->reviewed_at);
        $this->assertSame($this->supervisorProduksi->id, $approved->supervisor_reviewed_by);
        $this->assertNull($approved->points_awarded_at); // CAPA tidak memberi poin sejak 2026-09-27
        $this->assertNotNull($approved->published_at);
    }

    /**
     * TEST 4: Reject Supervisor menyimpan catatan_penolakan dan mengembalikan laporan untuk direvisi.
     */
    public function test_supervisor_reject_stores_catatan_penolakan_and_requests_revision(): void
    {
        $service = app(BaIncidentService::class);

        $incident = $service->saveDraft([
            'division_id' => $this->divisionProduksi->id,
            'tanggal_masalah' => '2026-09-02',
            'lokasi' => 'Jalur Conveyor 2',
            'sumberKetidaksesuaian' => 'audit_eksternal',
            'deskripsi_masalah' => 'Roller macet.',
            'why_1' => 'Baut penahan kendur.',
            'kesimpulan_akar_masalah' => 'Getaran tinggi mengendurkan baut.',
            'koreksi_deskripsi' => 'Kencangkan baut.',
            'korektif_deskripsi' => 'Ganti dengan mur pengunci (lock nut).',
        ], $this->employeeProduksi);

        $service->submit($incident, $this->employeeProduksi);

        $incident->refresh();

        // Reject tanpa alasan -> error
        try {
            $service->reject($incident, $this->supervisorProduksi, '');
            $this->fail('Harus melempar DomainException jika catatan penolakan kosong.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('alasan penolakan', $e->getMessage());
        }

        // Reject dengan alasan
        $rejectionNote = 'Analisis 5 Why belum mendalam, harap periksa juga spesifikasi torsi pengencangan baut.';
        $rejected = $service->reject($incident, $this->supervisorProduksi, $rejectionNote);

        $this->assertSame('revision_requested', $rejected->status);
        $this->assertSame($rejectionNote, $rejected->catatan_penolakan);
        // reviewed_by adalah kolom reviewer tahap HR; penolakan Supervisor tidak mengisinya.
        $this->assertNull($rejected->reviewed_by);

        // Activity log tercatat
        $this->assertDatabaseHas('ba_activity_logs', [
            'ba_incident_id' => $incident->id,
            'actor_id' => $this->supervisorProduksi->id,
            'action' => 'BA Ditolak Supervisor — Revisi Diminta',
            'note' => $rejectionNote,
        ]);

        // Cek via Livewire Show page bahwa banner penolakan dan tombol revisi tampil
        Livewire::actingAs($this->employeeProduksi)
            ->test(BaShow::class, ['incident' => $rejected])
            ->assertSeeHtml('Laporan BA Ini Perlu Revisi')
            ->assertSeeHtml($rejectionNote)
            ->assertSeeHtml('Edit Ulang &amp; Resubmit BA');
    }

    /**
     * TEST 5 (diperbarui 2026-09-27): laporan CAPA adalah kewajiban saat terjadi kesalahan. Setelah
     * disetujui final, laporan selesai di modul CAPA: tidak masuk Learning/Knowledge Repository dan
     * tidak memberi poin. Poin CPS ERA kini hanya dari video kontribusi (lihat VideoContributionFlowTest).
     */
    public function test_approved_report_stays_in_capa_without_learning_material_or_points(): void
    {
        $service = app(BaIncidentService::class);

        $incident = $service->saveDraft([
            'division_id' => $this->divisionProduksi->id,
            'tanggal_masalah' => '2026-09-01',
            'lokasi' => 'Boiler Room Utama',
            'sumberKetidaksesuaian' => 'temuan_patroli_hse',
            'deskripsi_masalah' => 'Tekanan uap boiler fluktuatif melebihi batas aman 6 bar.',
            'why_1' => 'Katup solenoid macet sebagian akibat kerak air.',
            'kesimpulan_akar_masalah' => 'Dosis bahan kimia water treatment tidak stabil.',
            'koreksi_deskripsi' => 'Penggantian solenoid valve dan descaling pipa.',
            'korektif_deskripsi' => 'Instalasi dosing pump otomatis terkalibrasi harian.',
        ], $this->employeeProduksi);

        $service->submit($incident, $this->employeeProduksi);

        // Supervisor meneruskan, lalu HR menyetujui final
        $service->approveAsSupervisor($incident->fresh(), $this->supervisorProduksi);
        $approved = $service->approve($incident->fresh(), $this->admin, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => 'Grafik tekanan boiler stabil pada 4.8 - 5.2 bar selama 14 hari pemantauan.',
        ]);

        $this->assertSame('approved', $approved->status);
        $this->assertDatabaseMissing('knowledge_documents', ['source_ba_id' => $incident->id]);
        $this->assertDatabaseMissing('learning_materials', ['source_ba_id' => $incident->id]);
        $this->assertDatabaseMissing('point_transactions', ['user_id' => $this->employeeProduksi->id]);
        $this->assertDatabaseCount('videos', 0);
    }
}
