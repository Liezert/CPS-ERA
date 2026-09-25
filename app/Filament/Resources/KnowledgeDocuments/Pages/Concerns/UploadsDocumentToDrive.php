<?php

namespace App\Filament\Resources\KnowledgeDocuments\Pages\Concerns;

use App\Services\GoogleDriveService;
use App\Services\KnowledgeDocumentUploader;
use DomainException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Komponen FileUpload menaruh berkas di disk lokal lebih dulu; di sini berkas
 * tersebut dipindahkan ke Google Drive dan kolom file_url diisi file ID Drive.
 */
trait UploadsDocumentToDrive
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function moveUploadedFileToDrive(array $data): array
    {
        $path = $data['file_url'] ?? null;

        // Kosong, sudah berupa file ID Drive, atau URL penuh: tidak ada yang perlu dipindahkan.
        if (blank($path) || GoogleDriveService::isDriveFileId($path) || str_starts_with($path, 'http')) {
            return $data;
        }

        try {
            $data['file_url'] = app(KnowledgeDocumentUploader::class)->uploadFromDisk($path);
        } catch (DomainException $exception) {
            // Detail (path, nama konfigurasi, respons Drive) hanya ke log; pengguna mendapat pesan generik.
            Log::error('Gagal memindahkan dokumen Knowledge ke Google Drive', [
                'path' => $path,
                'error' => $exception->getMessage(),
                'previous' => $exception->getPrevious()?->getMessage(),
            ]);

            // Validasi menggagalkan penyimpanan, sehingga tidak ada dokumen
            // tersimpan dengan berkas yang sebenarnya tidak pernah sampai di Drive.
            throw ValidationException::withMessages([
                'data.file_url' => 'Dokumen gagal diunggah ke Google Drive dan belum disimpan. Periksa koneksi Google Drive lalu coba lagi.',
            ]);
        }

        return $data;
    }
}
