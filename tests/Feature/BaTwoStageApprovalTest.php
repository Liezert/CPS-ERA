<?php

namespace Tests\Feature;

use App\Filament\Resources\BaIncidents\Pages\ViewBaIncident;
use App\Models\BaActivityLog;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\User;
use App\Models\Video;
use App\Services\BaIncidentService;
use App\Services\GoogleDriveService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use DomainException;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use RuntimeException;
use Tests\Concerns\FakesGoogleDrive;
use Tests\TestCase;

/**
 * Stage 4 alur approval CAPA: transisi status dua tahap (Supervisor → HR), siklus revisi, dan
 * penolakan permanen HR. Sejak 2026-09-27 CAPA tidak melampirkan video dan tidak memberi poin;
 * arsip video hanya ada pada laporan lama (dibuat manual di test ini).
 */
class BaTwoStageApprovalTest extends TestCase
{
    use FakesGoogleDrive;
    use RefreshDatabase;

    private BaIncidentService $service;

    private Division $produksi;

    private User $reporter;

    private User $supervisor;

    private User $quality;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->service = app(BaIncidentService::class);
        $this->produksi = Division::where('name', 'Produksi')->firstOrFail();
        $engineering = Division::where('name', 'Engineering')->firstOrFail();

        $this->reporter = $this->userWithRole('employee', $this->produksi, 'Budi Operator');
        $this->supervisor = $this->userWithRole('supervisor', $this->produksi, 'Joko SPV Produksi');
        $this->quality = $this->userWithRole('quality', $engineering, 'Sari Quality');
        $this->admin = $this->userWithRole('admin', $engineering, 'Admin CPS');
    }

    private function userWithRole(string $role, Division $division, string $name): User
    {
        $user = User::factory()->create(['name' => $name, 'division_id' => $division->id]);
        $user->assignRole($role);

        return $user;
    }

    private function draft(): BaIncident
    {
        return $this->service->saveDraft([
            'division_id' => $this->produksi->id,
            'lokasi' => 'Line Assembly 2',
            'deskripsi_masalah' => 'Produk cacat ditemukan di akhir line.',
            'why_1' => 'Mesin terlalu panas',
            'kesimpulan_akar_masalah' => 'Perawatan preventif tidak terjadwal.',
            'koreksi_deskripsi' => 'Bersihkan filter',
            'korektif_deskripsi' => 'Buat jadwal perawatan mingguan',
        ], $this->reporter);
    }

    private function submitted(): BaIncident
    {
        return $this->service->submit($this->draft(), $this->reporter);
    }

    /**
     * Video lampiran laporan lama (sebelum CAPA dipisah dari video), tersimpan di Drive.
     */
    private function attachLegacyDriveVideo(BaIncident $incident, string $fileId): Video
    {
        return Video::create([
            'title' => 'Video Bukti & Penanganan: '.$incident->nomor_ba,
            'video_url' => 'https://drive.google.com/file/d/'.$fileId.'/preview',
            'video_file_url' => $fileId,
            'division_id' => $incident->division_id,
            'created_by' => $incident->created_by,
            'creation_reason' => 'mandatory_incident',
            'ba_incident_id' => $incident->id,
            'status' => 'pending_supervisor',
        ]);
    }

    /**
     * @return list<string>
     */
    private function logActions(BaIncident $incident): array
    {
        return BaActivityLog::where('ba_incident_id', $incident->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('action')
            ->all();
    }

    private function driveDeleteSent(string $fileId): bool
    {
        return Http::recorded(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), "/drive/v3/files/{$fileId}"))->isNotEmpty();
    }

    public function test_happy_path_supervisor_then_hr_final_approval(): void
    {
        $incident = $this->submitted();
        $this->assertSame('pending_supervisor', $incident->status);

        $incident = $this->service->approveAsSupervisor($incident, $this->supervisor, 'Filter sudah diganti, dicek langsung di line.');
        $this->assertSame('pending_hr', $incident->status);
        $this->assertSame($this->supervisor->id, $incident->supervisor_reviewed_by);
        $this->assertNotNull($incident->supervisor_reviewed_at);
        $this->assertNull($incident->reviewed_by);

        // Catatan lapangan Supervisor terbaca HR saat review final.
        $note = $this->service->latestSupervisorNote($incident);
        $this->assertSame('Filter sudah diganti, dicek langsung di line.', $note->note);
        $this->assertTrue($note->actor->is($this->supervisor));

        $incident = $this->service->approve($incident, $this->quality, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => 'Tidak ada produk cacat selama 2 minggu.',
        ]);

        $this->assertSame('approved', $incident->status);
        $this->assertSame($this->quality->id, $incident->reviewed_by);
        $this->assertNotNull($incident->published_at);
        $this->assertSame('efektif', $incident->status_verifikasi);

        // CAPA adalah kewajiban saat terjadi kesalahan: tidak memberi poin dan tidak jadi materi Learning.
        $this->assertNull($incident->points_awarded_at);
        $this->assertDatabaseMissing('point_transactions', ['user_id' => $this->reporter->id]);
        $this->assertDatabaseMissing('learning_materials', ['source_ba_id' => $incident->id]);
        $this->assertDatabaseMissing('knowledge_documents', ['source_ba_id' => $incident->id]);

        $this->assertSame(
            ['Draft BA dibuat', 'BA Diserahkan', 'BA Disetujui Supervisor', 'BA Disetujui HR (Final)'],
            $this->logActions($incident)
        );
    }

    public function test_stages_cannot_be_skipped_or_repeated(): void
    {
        $incident = $this->submitted();

        // HR tidak bisa langsung approve final saat laporan masih di tahap Supervisor.
        $this->expectExceptionThrown(fn () => $this->service->approve($incident, $this->quality, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => 'Langsung disetujui.',
        ]));
        // Admin tidak punya fallback di tahap Supervisor.
        $this->expectExceptionThrown(fn () => $this->service->approveAsSupervisor($incident, $this->admin));

        $incident = $this->service->approveAsSupervisor($incident, $this->supervisor);

        // Supervisor tidak bisa approve final, dan tahap Supervisor tidak bisa diulang.
        $this->expectExceptionThrown(fn () => $this->service->approve($incident, $this->supervisor, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => 'Disetujui supervisor sendiri.',
        ]));
        $this->expectExceptionThrown(fn () => $this->service->approveAsSupervisor($incident, $this->supervisor));

        $incident = $this->service->approve($incident, $this->admin, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => 'Terverifikasi.',
        ]);

        // Approval final kedua ditolak; laporan CAPA tidak pernah menghasilkan poin.
        $this->expectExceptionThrown(fn () => $this->service->approve($incident, $this->quality, [
            'status_verifikasi' => 'efektif',
            'bukti_objektif' => 'Ulang.',
        ]));
        $this->assertSame(0, DB::table('point_transactions')->count());
    }

    public function test_revision_cycle_repeats_until_supervisor_approves(): void
    {
        $incident = $this->submitted();

        foreach (['Why kedua belum menjawab akar masalah.', 'Lampirkan hasil pengukuran suhu.'] as $round => $reason) {
            $incident = $this->service->reject($incident, $this->supervisor, $reason);
            $this->assertSame('revision_requested', $incident->status);
            $this->assertSame($reason, $incident->catatan_penolakan);
            $this->assertTrue($incident->isEditable());

            // Pelapor memperbaiki isi laporan (bisa berkali-kali) lalu mengirim ulang.
            $this->service->saveDraft(['why_2' => "Perbaikan ronde {$round}"], $this->reporter, $incident->id);
            $this->assertSame('revision_requested', $incident->fresh()->status);

            $incident = $this->service->submit($incident->fresh(), $this->reporter);
            $this->assertSame('pending_supervisor', $incident->status);
            $this->assertNull($incident->catatan_penolakan);
        }

        $incident = $this->service->approveAsSupervisor($incident, $this->supervisor);
        $this->assertSame('pending_hr', $incident->status);

        $this->assertSame([
            'Draft BA dibuat',
            'BA Diserahkan',
            'BA Ditolak Supervisor — Revisi Diminta',
            'Revisi BA disimpan',
            'BA Dikirim Ulang',
            'BA Ditolak Supervisor — Revisi Diminta',
            'Revisi BA disimpan',
            'BA Dikirim Ulang',
            'BA Disetujui Supervisor',
        ], $this->logActions($incident));
    }

    public function test_hr_rejection_is_permanent_and_archives_a_legacy_video_for_hr(): void
    {
        $this->connectDriveAccount();
        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response(['access_token' => 'token-akses-uji', 'expires_in' => 3600]),
            'www.googleapis.com/drive/v3/files/*' => Http::response(null, 204),
        ]);

        $incident = $this->submitted();
        $this->attachLegacyDriveVideo($incident, '1VideoArsipDriveIdUji001');
        $incident = $this->service->approveAsSupervisor($incident, $this->supervisor);

        $incident = $this->service->reject($incident, $this->quality, 'Tindakan korektif tidak relevan dengan akar masalah.');

        $this->assertSame('rejected', $incident->status);
        $this->assertSame($this->quality->id, $incident->reviewed_by);
        $this->assertSame('Tindakan korektif tidak relevan dengan akar masalah.', $incident->catatan_penolakan);
        $this->assertSame('draft', $incident->video->status);
        $this->assertDatabaseMissing('learning_materials', ['source_ba_id' => $incident->id]);

        // Tidak ada jalur revisi dari penolakan HR.
        $this->assertFalse($incident->isEditable());
        $this->expectExceptionThrown(fn () => $this->service->submit($incident, $this->reporter));
        $this->expectExceptionThrown(fn () => $this->service->reject($incident, $this->quality, 'Tolak lagi.'));

        // Hanya tim HR yang boleh menghapus rekaman arsip.
        $this->assertFalse($this->reporter->can('deleteArchivedVideo', $incident));
        $this->assertFalse($this->supervisor->can('deleteArchivedVideo', $incident));
        $this->assertTrue($this->quality->can('deleteArchivedVideo', $incident));
        $this->expectExceptionThrown(fn () => $this->service->deleteArchivedVideo($incident, $this->supervisor));

        $this->service->deleteArchivedVideo($incident, $this->quality);

        $this->assertTrue($this->driveDeleteSent('1VideoArsipDriveIdUji001'));
        $this->assertSame(0, Video::where('ba_incident_id', $incident->id)->count());
        $this->assertSame('BA Ditolak HR (Final)', $this->logActions($incident)[3]);
        $this->assertSame('Video BA Dihapus', last($this->logActions($incident)));
    }

    public function test_filament_actions_follow_the_current_stage(): void
    {
        $incident = $this->submitted();

        // Tahap Supervisor: hanya Supervisor divisi pelapor, dengan catatan lapangan opsional.
        Livewire::actingAs($this->quality)
            ->test(ViewBaIncident::class, ['record' => $incident->getRouteKey()])
            ->assertActionHidden('approve')
            ->assertActionHidden('reject');

        Livewire::actingAs($this->supervisor)
            ->test(ViewBaIncident::class, ['record' => $incident->getRouteKey()])
            ->assertActionVisible('approve')
            ->callAction('approve', ['catatan' => 'Sudah dicek di lapangan.'])
            ->assertHasNoActionErrors();

        $incident->refresh();
        $this->assertSame('pending_hr', $incident->status);

        // Tahap HR: Supervisor tidak lagi bisa bertindak; HR mengisi evaluasi formal.
        Livewire::actingAs($this->supervisor)
            ->test(ViewBaIncident::class, ['record' => $incident->getRouteKey()])
            ->assertActionHidden('approve');

        Livewire::actingAs($this->quality)
            ->test(ViewBaIncident::class, ['record' => $incident->getRouteKey()])
            ->mountAction('approve')
            ->assertMountedActionModalSee('Catatan Supervisor (Joko SPV Produksi): "Sudah dicek di lapangan."')
            ->fillForm(['status_verifikasi' => 'efektif', 'bukti_objektif' => 'Tidak ada cacat 2 minggu.'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame('approved', $incident->fresh()->status);
    }

    public function test_division_supervisor_seeder_covers_all_eleven_reportable_divisions_idempotently(): void
    {
        $seeder = new UserSeeder;
        $seeder->seedDivisionSupervisors();
        $seeder->seedDivisionSupervisors();

        $reportable = Division::reportable()->get();
        $this->assertCount(12, $reportable);

        foreach ($reportable as $division) {
            $this->assertSame(
                1,
                User::role('supervisor')->where('division_id', $division->id)->where('email', 'like', 'spv.%')->count(),
                "Divisi {$division->name} harus punya tepat satu supervisor dummy."
            );
        }
    }

    public function test_submitted_status_migration_maps_to_pending_supervisor_and_stops_on_unknown_status(): void
    {
        $migration = require database_path('migrations/2026_09_22_000002_move_submitted_ba_incidents_to_pending_supervisor.php');

        $submitted = $this->draft();
        $submitted->update(['status' => 'submitted']);

        $migration->up();
        $this->assertSame('pending_supervisor', $submitted->fresh()->status);

        $migration->down();
        $this->assertSame('submitted', $submitted->fresh()->status);

        $submitted->update(['status' => 'reviewed']);
        $this->expectException(RuntimeException::class);
        $migration->up();
    }

    /**
     * Pastikan aksi ditolak (DomainException, atau QueryException untuk simulasi galat simpan)
     * tanpa menghentikan sisa test.
     */
    private function expectExceptionThrown(callable $action): void
    {
        try {
            $action();
        } catch (DomainException|QueryException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('Aksi seharusnya ditolak.');
    }
}
