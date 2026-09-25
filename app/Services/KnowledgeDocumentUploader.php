<?php

namespace App\Services;

use DomainException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Memindahkan berkas dokumen Knowledge Repository ke Google Drive.
 *
 * Dokumen (PDF/Office) umumnya jauh di bawah 5 MB sehingga memakai multipart
 * upload; video CAPA yang berukuran besar memakai jalur resumable di
 * BaIncidentService.
 */
class KnowledgeDocumentUploader
{
    public function __construct(protected GoogleDriveService $drive) {}

    /**
     * Unggah berkas yang sudah terlanjur tersimpan di disk lokal
     * (mis. hasil komponen FileUpload Filament), lalu hapus salinan lokalnya.
     *
     * @return string File ID Drive
     */
    public function uploadFromDisk(string $path, string $disk = 'public'): string
    {
        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            throw new DomainException("Berkas dokumen tidak ditemukan di penyimpanan sementara: {$path}");
        }

        $fileId = $this->push(
            $storage->path($path),
            $this->fileName(basename($path)),
            $storage->mimeType($path) ?: null,
        );

        // Berkas sudah aman di Drive; salinan lokal tidak perlu disimpan.
        $storage->delete($path);

        return $fileId;
    }

    protected function push(string $absolutePath, string $fileName, ?string $mimeType): string
    {
        $folderId = config('services.google_drive.folder_id_knowledge');

        if (blank($folderId)) {
            throw new DomainException(
                'Folder Google Drive untuk Knowledge Repository belum dikonfigurasi (GOOGLE_DRIVE_FOLDER_ID_KNOWLEDGE).'
            );
        }

        try {
            $fileId = $this->drive->uploadFile($absolutePath, $folderId, $fileName, $mimeType);
            $this->drive->setPublicPermission($fileId);

            return $fileId;
        } catch (RuntimeException $exception) {
            Log::error('Unggah dokumen Knowledge ke Google Drive gagal', [
                'file_name' => $fileName,
                'error' => $exception->getMessage(),
            ]);

            throw new DomainException(
                'Gagal mengunggah dokumen ke Google Drive. Periksa koneksi lalu coba lagi. Dokumen belum disimpan.',
                previous: $exception
            );
        }
    }

    /**
     * Beri awalan waktu agar berkas bernama sama tidak tertukar di folder Drive.
     */
    protected function fileName(string $originalName): string
    {
        return now()->format('Ymd-His').'-'.$originalName;
    }
}
