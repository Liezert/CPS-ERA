<?php

namespace Tests\Feature;

use App\Livewire\Ba\Show as BaShow;
use App\Livewire\Knowledge\Index as KnowledgeIndex;
use App\Livewire\Video\Create as VideoCreate;
use App\Livewire\Video\Show as VideoShow;
use App\Models\BaIncident;
use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\User;
use App\Models\Video;
use App\Services\VideoApprovalService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\FakesGoogleDrive;
use Tests\TestCase;

/**
 * Integrasi unggahan berkas ke Google Drive: video kontribusi (resumable) dan
 * dokumen Knowledge Repository (multipart), termasuk jalur galat dan
 * penyajiannya di antarmuka. Video lama milik laporan CAPA tetap bisa diputar.
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function submitVideo(array $overrides = []): Video
    {
        return app(VideoApprovalService::class)->submit($this->employee, array_merge([
            'title' => 'Cara Membersihkan Nozzle Injeksi',
            'description' => 'Langkah aman membersihkan nozzle.',
            'video_file' => UploadedFile::fake()->createWithContent('penanganan.mp4', str_repeat('v', 4096)),
            'video_external_link' => null,
        ], $overrides));
    }

    public function test_video_kontribusi_diunggah_resumable_dan_file_id_tersimpan(): void
    {
        $fileId = $this->fakeGoogleDrive('1VideoKontribusiIdUji');
        $berkas = UploadedFile::fake()->createWithContent('penanganan.mp4', str_repeat('v', 4096));
        $mimePengunggah = $berkas->getMimeType();

        $video = $this->submitVideo(['video_file' => $berkas]);

        // Kolom menyimpan file ID Drive, bukan path lokal.
        $this->assertSame($fileId, $video->video_file_url);
        $this->assertStringNotContainsString('/storage/', (string) $video->video_file_url);
        $this->assertSame("https://drive.google.com/file/d/{$fileId}/preview", $video->video_url);
        $this->assertSame('pending_hr', $video->status);
        $this->assertSame(VideoApprovalService::CREATION_REASON, $video->creation_reason);
        $this->assertNull($video->ba_incident_id);

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

        $video = $this->submitVideo([
            'video_file' => null,
            'video_external_link' => 'https://drive.google.com/file/d/tautan-manual-123456789/view',
        ]);

        $this->assertNull($video->video_file_url);
        $this->assertSame('https://drive.google.com/file/d/tautan-manual-123456789/view', $video->video_external_link);
        $this->assertSame('pending_hr', $video->status);

        // Tidak ada unggahan yang dikirim untuk jalur tautan manual.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/upload/drive/v3/files'));
    }

    public function test_kegagalan_unggah_tidak_menyimpan_video_dan_memberi_pesan_jelas(): void
    {
        $this->fakeGoogleDriveUploadFailure();

        try {
            $this->submitVideo();

            $this->fail('Unggahan gagal seharusnya melempar DomainException.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Gagal mengunggah video ke Google Drive', $exception->getMessage());
        }

        $this->assertDatabaseCount('videos', 0);
    }

    public function test_livewire_menampilkan_pesan_galat_dan_tetap_di_halaman_saat_unggah_gagal(): void
    {
        $this->fakeGoogleDriveUploadFailure();

        Livewire::actingAs($this->employee)
            ->test(VideoCreate::class)
            ->set('title', 'Cara Membersihkan Nozzle Injeksi')
            ->set('videoFile', UploadedFile::fake()->createWithContent('penanganan.mp4', str_repeat('v', 4096)))
            ->call('submit')
            ->assertHasErrors('video')
            ->assertNoRedirect()
            ->assertSee('Gagal mengunggah video ke Google Drive');

        $this->assertDatabaseCount('videos', 0);
    }

    public function test_berkas_drive_dihapus_kembali_bila_penyimpanan_gagal(): void
    {
        $fileId = $this->fakeGoogleDrive('1VideoYatimPiatu');

        try {
            // Kategori yang tidak ada melanggar foreign key: penyimpanan gagal setelah unggahan berhasil.
            $this->submitVideo(['learning_category_id' => 999999]);

            $this->fail('Kegagalan penyimpanan seharusnya melempar exception.');
        } catch (\Throwable $exception) {
            $this->assertNotInstanceOf(DomainException::class, $exception);
        }

        // Berkas yang terlanjur naik dibersihkan agar tidak menjadi sampah di Drive.
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), "/drive/v3/files/{$fileId}"));
        $this->assertDatabaseCount('videos', 0);
    }

    public function test_pemutar_video_memakai_iframe_preview_drive(): void
    {
        $fileId = $this->fakeGoogleDrive('1VideoKontribusiIdUji');
        $video = $this->submitVideo();

        Livewire::actingAs($this->employee)
            ->test(VideoShow::class, ['video' => $video])
            ->assertSeeHtml("https://drive.google.com/file/d/{$fileId}/preview")
            ->assertDontSeeHtml('<source src=');
    }

    /**
     * Laporan lama (sebelum CAPA dipisah dari video) yang videonya masih berkas lokal.
     */
    protected function legacyIncident(): BaIncident
    {
        return BaIncident::create([
            'nomor_ba' => 'BA-2026-0101',
            'title' => 'CAPA: laporan lama',
            'division_id' => $this->division->id,
            'deskripsi_masalah' => 'Suhu nozzle melampaui batas',
            'status' => 'approved',
            'created_by' => $this->employee->id,
        ]);
    }

    public function test_pemutar_video_masih_memutar_berkas_lokal_warisan(): void
    {
        $incident = $this->legacyIncident();

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
