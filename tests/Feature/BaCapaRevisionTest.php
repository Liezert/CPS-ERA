<?php

namespace Tests\Feature;

use App\Livewire\Ba\Create as BaCreate;
use App\Livewire\Ba\Show as BaShow;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\KnowledgeDocument;
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
     * TEST 1: Submission tidak bisa lanjut ke status 'submitted' kalau Step 2 (video) belum lengkap (dua-duanya kosong).
     */
    public function test_submission_cannot_proceed_to_submitted_if_step_2_video_is_incomplete(): void
    {
        // 1. Lewat Livewire Multi-step:
        $lw = Livewire::actingAs($this->employeeProduksi)
            ->test(BaCreate::class)
            ->set('divisionId', $this->divisionProduksi->id)
            ->set('tanggalMasalah', '2026-09-10')
            ->set('lokasi', 'Lini Injeksi Moulding 03')
            ->set('sumberKetidaksesuaian', 'audit')
            ->set('deskripsiMasalah', 'Ditemukan kebocoran oli pelumas pada piston utama.')
            ->set('why1', 'Seal hidrolik mengalami keretakan mikro.')
            ->set('kesimpulanAkarMasalah', 'Degradasi material seal akibat temperatur berlebih.')
            ->set('koreksiDeskripsi', 'Penggantian seal darurat dan pembersihan tumpahan oli.')
            ->set('korektifDeskripsi', 'Pemasangan sensor temperatur otomatis pada pompa sirkulasi.')
            ->call('nextStep')
            ->assertSet('step', 2);

        // Di Step 2, tanpa upload video atau external link, tombol submit harus memicu validasi error
        $lw->call('submit')
            ->assertHasErrors(['videoRequired']);

        // Incident harus tetap berstatus 'draft', tidak boleh 'submitted'
        $incidentId = $lw->get('baIncidentId');
        $this->assertNotNull($incidentId);

        $incident = BaIncident::find($incidentId);
        $this->assertSame('draft', $incident->status);
        $this->assertFalse($incident->isSubmitted());

        // 2. Lewat Service class:
        $service = app(BaIncidentService::class);
        $this->expectException(DomainException::class);
        $service->submitWithVideo($incident, $this->employeeProduksi, [
            'video_file' => null,
            'video_external_link' => null,
        ]);
    }

    /**
     * TEST 2: Data Step 1 tidak hilang kalau user klik "Kembali" dari Step 2 (tersimpan di database sebagai draft).
     */
    public function test_step_1_data_is_preserved_in_database_draft_when_user_clicks_kembali_from_step_2(): void
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
            ->set('korektifWaktu', '3 Hari')
            ->set('isPotensiRisiko', true)
            ->set('isPotensiPeluang', false)
            ->call('nextStep')
            ->assertSet('step', 2);

        // Verifikasi data tersimpan sebagai draft di database
        $incidentId = $lw->get('baIncidentId');
        $this->assertNotNull($incidentId);

        $incident = BaIncident::find($incidentId);
        $this->assertNotNull($incident);
        $this->assertSame('draft', $incident->status);
        $this->assertSame('Warehouse Rack A-12', $incident->lokasi);
        $this->assertSame('keluhan_pelanggan', $incident->sumber_ketidaksesuaian);
        $this->assertSame('Kapasitas angkut melebihi batas beban maksimum.', $incident->why_1);
        $this->assertSame('Budi Warehouse', $incident->koreksi_pic);
        $this->assertTrue($incident->is_potensi_risiko);

        // Klik "Kembali" ke Step 1
        $lw->call('previousStep')
            ->assertSet('step', 1);

        // Pastikan state Livewire tetap mempertahankan seluruh data
        $this->assertSame('Warehouse Rack A-12', $lw->get('lokasi'));
        $this->assertSame('keluhan_pelanggan', $lw->get('sumberKetidaksesuaian'));
        $this->assertSame('Kapasitas angkut melebihi batas beban maksimum.', $lw->get('why1'));
        $this->assertSame('Budi Warehouse', $lw->get('koreksiPic'));
        $this->assertTrue($lw->get('isPotensiRisiko'));

        // Ubah sedikit data dan simpan draft lagi
        $lw->set('lokasi', 'Warehouse Rack B-05')
            ->call('nextStep')
            ->assertSet('step', 2);

        $incident->refresh();
        $this->assertSame('Warehouse Rack B-05', $incident->lokasi);
        $this->assertSame('draft', $incident->status);
    }

    /**
     * TEST 3: Approve wajib mengisi status_verifikasi, tidak bisa approve tanpa itu.
     */
    public function test_approve_requires_status_verifikasi_and_cannot_approve_without_it(): void
    {
        $service = app(BaIncidentService::class);

        // Buat draft lalu submit dengan video external link
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

        $service->submitWithVideo($incident, $this->employeeProduksi, [
            'video_external_link' => 'https://youtube.com/watch?v=sample123',
        ]);

        $incident->refresh();
        $this->assertSame('submitted', $incident->status);

        // 1. Coba approve tanpa status_verifikasi
        try {
            $service->approve($incident, $this->supervisorProduksi, [
                'status_verifikasi' => null,
            ]);
            $this->fail('Harus melempar DomainException jika status_verifikasi kosong.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Status verifikasi', $e->getMessage());
        }

        // 2. Coba status_verifikasi = efektif tanpa bukti_objektif
        try {
            $service->approve($incident, $this->supervisorProduksi, [
                'status_verifikasi' => 'efektif',
                'bukti_objektif' => '',
            ]);
            $this->fail('Harus melempar DomainException jika status efektif tidak menyertakan bukti_objektif.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Bukti objektif', $e->getMessage());
        }

        // 3. Coba status_verifikasi = tidak_efektif tanpa alasan_tidak_efektif
        try {
            $service->approve($incident, $this->supervisorProduksi, [
                'status_verifikasi' => 'tidak_efektif',
                'alasan_tidak_efektif' => '',
            ]);
            $this->fail('Harus melempar DomainException jika status tidak efektif tidak menyertakan alasan.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Alasan ketidakefektifan', $e->getMessage());
        }

        // 4. Approve dengan data verifikasi lengkap
        $approved = $service->approve($incident, $this->supervisorProduksi, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => 'Sensor telah diuji 24 jam nonstop tanpa alarm kegagalan.',
        ]);

        $this->assertSame('approved', $approved->status);
        $this->assertSame('efektif', $approved->status_verifikasi);
        $this->assertSame('Sensor telah diuji 24 jam nonstop tanpa alarm kegagalan.', $approved->bukti_objektif);
        $this->assertSame($this->supervisorProduksi->id, $approved->reviewed_by);
        $this->assertNotNull($approved->reviewed_at);
    }

    /**
     * TEST 4: Reject menyimpan catatan_penolakan, status berubah jadi 'rejected'.
     */
    public function test_reject_stores_catatan_penolakan_and_sets_status_to_rejected(): void
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

        $service->submitWithVideo($incident, $this->employeeProduksi, [
            'video_external_link' => 'https://vimeo.com/987654321',
        ]);

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

        $this->assertSame('rejected', $rejected->status);
        $this->assertSame($rejectionNote, $rejected->catatan_penolakan);
        $this->assertSame($this->supervisorProduksi->id, $rejected->reviewed_by);
        $this->assertNotNull($rejected->reviewed_at);

        // Activity log tercatat
        $this->assertDatabaseHas('ba_activity_logs', [
            'ba_incident_id' => $incident->id,
            'actor_id' => $this->supervisorProduksi->id,
            'action' => 'BA Ditolak / Perbaikan Diminta',
            'note' => $rejectionNote,
        ]);

        // Cek via Livewire Show page bahwa banner penolakan dan tombol revisi tampil
        Livewire::actingAs($this->employeeProduksi)
            ->test(BaShow::class, ['incident' => $rejected])
            ->assertSeeHtml('Laporan BA Ini Ditolak / Perlu Revisi')
            ->assertSeeHtml($rejectionNote)
            ->assertSeeHtml('Edit Ulang &amp; Resubmit BA');
    }

    /**
     * TEST 5: Setelah approved, entri lesson_learned otomatis muncul di knowledge_documents dan poin diberikan.
     */
    public function test_approving_automatically_creates_lesson_learned_and_awards_points(): void
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

        $service->submitWithVideo($incident, $this->employeeProduksi, [
            'video_external_link' => 'https://company.video/lesson-boiler-01',
        ]);

        $incident->refresh();

        // Pastikan belum ada dokumen lesson learned sebelum approve
        $this->assertDatabaseMissing('knowledge_documents', [
            'source_ba_id' => $incident->id,
        ]);

        // Supervisor menyetujui
        $service->approve($incident, $this->supervisorProduksi, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => 'Grafik tekanan boiler stabil pada 4.8 - 5.2 bar selama 14 hari pemantauan.',
        ]);

        // Verifikasi entri knowledge_documents bertipe lesson_learned otomatis terbuat
        $this->assertDatabaseHas('knowledge_documents', [
            'source_ba_id' => $incident->id,
            'division_id' => $this->divisionProduksi->id,
            'type' => 'lesson_learned',
            'status' => 'published',
        ]);

        $lessonDoc = KnowledgeDocument::where('source_ba_id', $incident->id)->first();
        $this->assertNotNull($lessonDoc);
        $this->assertStringContainsString($incident->nomor_ba, $lessonDoc->title);
        $this->assertStringContainsString('Tekanan uap boiler fluktuatif', $lessonDoc->description);
        $this->assertStringContainsString('Dosis bahan kimia water treatment tidak stabil', $lessonDoc->description);
        $this->assertStringContainsString('Instalasi dosing pump otomatis', $lessonDoc->description);

        // Verifikasi point transaction untuk pembuat BA (PRD v2.0: 1 poin KPI ba_video_approved)
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->employeeProduksi->id,
            'points' => 1,
            'source_type' => 'ba_video_approved',
            'source_id' => $incident->id,
        ]);

        // Verifikasi video terkait statusnya disinkronkan ke published
        $video = Video::where('ba_incident_id', $incident->id)->first();
        $this->assertNotNull($video);
        $this->assertSame('published', $video->status);
    }
}
