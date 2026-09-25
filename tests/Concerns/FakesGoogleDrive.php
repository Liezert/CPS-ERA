<?php

namespace Tests\Concerns;

use App\Models\GoogleDriveToken;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * Menyiapkan koneksi Drive palsu untuk pengujian: satu baris token OAuth dan
 * tiruan seluruh endpoint Google, sehingga tidak ada permintaan jaringan nyata.
 */
trait FakesGoogleDrive
{
    /**
     * @param  string  $fileId  File ID yang dikembalikan endpoint unggahan tiruan.
     */
    protected function fakeGoogleDrive(string $fileId = 'drive-file-id-uji-000001'): string
    {
        $this->connectDriveAccount();

        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response(['access_token' => 'token-akses-uji', 'expires_in' => 3600]),

            // Unggahan: multipart membalas file ID langsung, resumable membuka sesi
            // lalu membalas file ID pada potongan terakhir.
            'www.googleapis.com/upload/drive/v3/files*' => function ($request) use ($fileId) {
                if ($request->method() === 'POST' && str_contains($request->url(), 'uploadType=resumable')) {
                    return Http::response('', 200, [
                        'Location' => 'https://www.googleapis.com/upload/drive/v3/files?upload_id=sesi-uji',
                    ]);
                }

                return Http::response(['id' => $fileId]);
            },

            // Izin publik & penghapusan berkas.
            'www.googleapis.com/drive/v3/files/*' => Http::response(['id' => 'anyoneWithLink']),
        ]);

        return $fileId;
    }

    /**
     * Sambungkan akun Drive palsu tanpa memasang tiruan HTTP apa pun.
     * Http::fake() bersifat menggabungkan (stub pertama yang cocok menang),
     * sehingga penyiapan galat harus memasang tiruannya sendiri dari awal.
     */
    protected function connectDriveAccount(): void
    {
        config()->set('services.google_drive.client_id', 'klien-uji.apps.googleusercontent.com');
        config()->set('services.google_drive.client_secret', 'rahasia-klien-uji');
        config()->set('services.google_drive.folder_id_ba', 'folder-ba-uji');
        config()->set('services.google_drive.folder_id_knowledge', 'folder-knowledge-uji');

        GoogleDriveToken::query()->delete();
        GoogleDriveToken::create([
            'refresh_token' => Crypt::encryptString('refresh-token-uji'),
            'connected_email' => 'drive-cps@gmail.com',
            'connected_at' => now(),
        ]);
    }

    /**
     * Buat kegagalan unggahan Drive (token tetap valid) untuk menguji jalur galat.
     */
    protected function fakeGoogleDriveUploadFailure(): void
    {
        $this->connectDriveAccount();

        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response(['access_token' => 'token-akses-uji']),
            'www.googleapis.com/upload/drive/v3/files*' => Http::response(['error' => 'backendError'], 500),
            'www.googleapis.com/drive/v3/files/*' => Http::response(['id' => 'anyoneWithLink']),
        ]);
    }
}
