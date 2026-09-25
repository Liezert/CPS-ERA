<?php

namespace Tests\Feature;

use App\Livewire\Ba\Create as BaCreate;
use App\Livewire\Ba\Show as BaShow;
use App\Livewire\Knowledge\Index as KnowledgeIndex;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Concerns\FakesGoogleDrive;
use Tests\TestCase;

/**
 * Integrasi unggahan berkas ke Google Drive: video CAPA (resumable) dan
 * dokumen Knowledge Repository (multipart), termasuk jalur galat dan
 * penyajiannya di antarmuka.
 */
class GoogleDriveUploadIntegrationTest extends TestCase
{
    use FakesGoogleDrive, RefreshDatabase;

    protected Division $division;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->division = Division::where('name', 'Produksi')->firstOrFail();

        $this->employee = User::factory()->create(['division_id' => $this->division->id]);
        $this->employee->assignRole('employee');
    }

    protected function draftIncident(): BaIncident
    {
        return BaIncident::create([
            'nomor_ba' => 'BA-2026-0101',
            'title' => 'CAPA: uji unggah Drive',
            'division_id' => $this->division->id,
            'tanggal_pengisian' => '2026-09-20',
            'sumber_ketidaksesuaian' => 'audit',
            'tanggal_masalah' => '2026-09-19',
            'lokasi' => 'Lini Injeksi Nozzle 02',
            'deskripsi_masalah' => 'Suhu nozzle melampaui batas',
            'why_1' => 'Temperatur naik',
            'kesimpulan_akar_masalah' => 'Tidak ada IK pembersihan',
            'koreksi_deskripsi' => 'Mesin dimatikan',
            'korektif_deskripsi' => 'Revisi checklist',
            'status' => 'draft',
            'created_by' => $this->employee->id,
        ]);
    }

    public function test_video_capa_diunggah_resumable_dan_file_id_tersimpan(): void
    {
        $fileId = $this->fakeGoogleDrive('1VideoCapaDriveIdUji');
        $incident = $this->draftIncident();
        $berkas = UploadedFile::fake()->createWithContent('penanganan.mp4', str_repeat('v', 4096));
        $mimePengunggah = $berkas->getMimeType();

        app(BaIncidentService::class)->submitWithVideo($incident, $this->employee, [
            'video_file' => $berkas,
            'video_external_link' => null,
        ]);

        $video = Video::where('ba_incident_id', $incident->id)->sole();

        // Kolom menyimpan file ID Drive, bukan path lokal seperti sebelumnya.
        $this->assertSame($fileId, $video->video_file_url);
        $this->assertStringNotContainsString('/storage/', (string) $video->video_file_url);
        $this->assertSame("https://drive.google.com/file/d/{$fileId}/preview", $video->video_url);
        $this->assertSame('pending_supervisor', $incident->fresh()->status);

        // Jalur resumable dipakai, dengan MIME dari UploadedFile pengunggah.
        Http::assertSent(function ($request) use ($mimePengunggah): bool {
            if ($request->method() !== 'POST' || ! str_contains($request->url(), 'uploadType=resumable')) {
                return false;
            }

            $this->assertSame($mimePengunggah, $request->header('X-Upload-Content-Type')[0]);
            $this->assertSame(['folder-ba-uji'], $request->data()['parents']);

            return true;
        });

        // Izin publik disetel setelah unggahan selesai.
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), "/drive/v3/files/{$fileId}/permissions")
            && $request->data() === ['role' => 'reader', 'type' => 'anyone']);
    }

    public function test_tautan_eksternal_manual_tidak_menyentuh_drive(): void
    {
        $this->fakeGoogleDrive();
        $incident = $this->draftIncident();

        app(BaIncidentService::class)->submitWithVideo($incident, $this->employee, [
            'video_file' => null,
            'video_external_link' => 'https://drive.google.com/file/d/tautan-manual-123/view',
        ]);

        $video = Video::where('ba_incident_id', $incident->id)->sole();

        $this->assertNull($video->video_file_url);
        $this->assertSame('https://drive.google.com/file/d/tautan-manual-123/view', $video->video_external_link);
        $this->assertSame('pending_supervisor', $incident->fresh()->status);

        // Tidak ada unggahan yang dikirim untuk jalur tautan manual.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/upload/drive/v3/files'));
    }

    public function test_kegagalan_unggah_tidak_menyimpan_laporan_dan_memberi_pesan_jelas(): void
    {
        $this->fakeGoogleDriveUploadFailure();
        $incident = $this->draftIncident();

        try {
            app(BaIncidentService::class)->submitWithVideo($incident, $this->employee, [
                'video_file' => UploadedFile::fake()->createWithContent('penanganan.mp4', str_repeat('v', 4096)),
                'video_external_link' => null,
            ]);

            $this->fail('Unggahan gagal seharusnya melempar DomainException.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Gagal mengunggah video ke Google Drive', $exception->getMessage());
        }

        // Laporan tidak boleh tersimpan dengan video kosong/rusak.
        $this->assertSame('draft', $incident->fresh()->status);
        $this->assertDatabaseCount('videos', 0);
    }

    public function test_livewire_menampilkan_pesan_galat_dan_tetap_di_halaman_saat_unggah_gagal(): void
    {
        $this->fakeGoogleDriveUploadFailure();
        $incident = $this->draftIncident();

        Livewire::actingAs($this->employee)
            ->test(BaCreate::class, ['incidentId' => $incident->id])
            ->set('step', 2)
            ->set('videoFile', UploadedFile::fake()->createWithContent('penanganan.mp4', str_repeat('v', 4096)))
            ->call('submit')
            ->assertHasErrors('videoRequired')
            ->assertNoRedirect()
            ->assertSee('Gagal mengunggah video ke Google Drive');

        $this->assertSame('draft', $incident->fresh()->status);
    }

    public function test_berkas_drive_dihapus_kembali_bila_penyimpanan_gagal(): void
    {
        $fileId = $this->fakeGoogleDrive('1VideoYatimPiatu');
        $incident = $this->draftIncident();

        // Simulasikan kegagalan penyimpanan setelah unggahan berhasil.
        Schema::drop('ba_activity_logs');

        try {
            app(BaIncidentService::class)->submitWithVideo($incident, $this->employee, [
                'video_file' => UploadedFile::fake()->createWithContent('penanganan.mp4', str_repeat('v', 4096)),
                'video_external_link' => null,
            ]);

            $this->fail('Kegagalan penyimpanan seharusnya melempar exception.');
        } catch (\Throwable $exception) {
            $this->assertNotInstanceOf(DomainException::class, $exception);
        }

        // Berkas yang terlanjur naik dibersihkan agar tidak menjadi sampah di Drive.
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), "/drive/v3/files/{$fileId}"));
    }

    public function test_pemutar_video_memakai_iframe_preview_drive(): void
    {
        $fileId = $this->fakeGoogleDrive('1VideoCapaDriveIdUji');
        $incident = $this->draftIncident();

        app(BaIncidentService::class)->submitWithVideo($incident, $this->employee, [
            'video_file' => UploadedFile::fake()->createWithContent('penanganan.mp4', str_repeat('v', 4096)),
            'video_external_link' => null,
        ]);

        Livewire::actingAs($this->employee)
            ->test(BaShow::class, ['incident' => $incident->fresh()])
            ->assertSeeHtml("https://drive.google.com/file/d/{$fileId}/preview")
            ->assertDontSeeHtml('<source src=');
    }

    public function test_pemutar_video_masih_memutar_berkas_lokal_warisan(): void
    {
        $incident = $this->draftIncident();

        Video::create([
            'title' => 'Video lama',
            'ba_incident_id' => $incident->id,
            'division_id' => $this->division->id,
            'created_by' => $this->employee->id,
            'video_file_url' => '/storage/videos/mandatory/lama.mp4',
            'video_url' => '/storage/videos/mandatory/lama.mp4',
            'creation_reason' => 'mandatory_incident',
            'status' => 'published',
        ]);

        Livewire::actingAs($this->employee)
            ->test(BaShow::class, ['incident' => $incident->fresh()])
            ->assertSeeHtml('<source src=')
            ->assertSeeHtml('storage/videos/mandatory/lama.mp4');
    }

    public function test_dokumen_knowledge_drive_memberi_url_unduh_dan_pratinjau(): void
    {
        $driveDoc = KnowledgeDocument::create([
            'title' => 'SOP Pemeliharaan Mesin',
            'division_id' => $this->division->id,
            'type' => 'sop',
            'status' => 'published',
            'created_by' => $this->employee->id,
            'file_url' => '1DokumenDriveIdUji0001',
        ]);

        $this->assertSame('https://drive.google.com/file/d/1DokumenDriveIdUji0001/view', $driveDoc->download_url);
        $this->assertSame('https://drive.google.com/file/d/1DokumenDriveIdUji0001/preview', $driveDoc->preview_url);

        $legacyDoc = KnowledgeDocument::create([
            'title' => 'SOP Lama',
            'division_id' => $this->division->id,
            'type' => 'sop',
            'status' => 'published',
            'created_by' => $this->employee->id,
            'file_url' => 'knowledge-documents/files/sop-lama.pdf',
        ]);

        // Berkas warisan tetap dilayani dari penyimpanan lokal, tanpa pratinjau Drive.
        $this->assertStringContainsString('knowledge-documents/files/sop-lama.pdf', $legacyDoc->download_url);
        $this->assertNull($legacyDoc->preview_url);
    }

    public function test_halaman_knowledge_menyematkan_pratinjau_dokumen_drive(): void
    {
        $document = KnowledgeDocument::create([
            'title' => 'SOP Pemeliharaan Mesin',
            'division_id' => $this->division->id,
            'type' => 'sop',
            'status' => 'published',
            'created_by' => $this->employee->id,
            'file_url' => '1DokumenDriveIdUji0001',
        ]);

        Livewire::actingAs($this->employee)
            ->test(KnowledgeIndex::class)
            ->call('showDocument', $document->id)
            ->assertSeeHtml('https://drive.google.com/file/d/1DokumenDriveIdUji0001/preview')
            ->assertSee('Pratinjau Berkas');
    }
}
