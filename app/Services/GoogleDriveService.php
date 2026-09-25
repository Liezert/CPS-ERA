<?php

namespace App\Services;

use App\Models\GoogleDriveToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien Google Drive minimalis tanpa SDK resmi (google/apiclient) maupun
 * flysystem adapter, karena keduanya menarik dependency yang bentrok dengan
 * guzzlehttp/guzzle 8.x yang sudah terkunci di project ini.
 *
 * Autentikasi memakai OAuth 2.0 milik pengguna: satu akun Google dihubungkan
 * sekali lewat layar consent (lihat GoogleDriveOAuthController), refresh token
 * disimpan terenkripsi, lalu ditukar menjadi access token saat dibutuhkan.
 * Berkas yang diunggah dimiliki akun tersebut dan memakai kuotanya.
 *
 * @see https://developers.google.com/identity/protocols/oauth2/web-server
 */
class GoogleDriveService
{
    public const ACCESS_TOKEN_CACHE_KEY = 'google_drive_access_token';

    public const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    public const UPLOAD_ENDPOINT = 'https://www.googleapis.com/upload/drive/v3/files';

    public const FILES_ENDPOINT = 'https://www.googleapis.com/drive/v3/files';

    /**
     * Least privilege: aplikasi hanya boleh menyentuh berkas yang dibuatnya
     * sendiri, bukan seluruh isi Drive akun yang terhubung.
     */
    public const SCOPE = 'https://www.googleapis.com/auth/drive.file';

    /**
     * @deprecated Jalur service account tidak dipakai lagi (tanpa kuota penyimpanan).
     */
    protected const GRANT_TYPE = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    /**
     * Besar potongan unggahan resumable (8 MiB). Google mewajibkan setiap
     * potongan selain potongan terakhir kelipatan 256 KiB.
     */
    protected const RESUMABLE_CHUNK_BYTES = 8388608;

    protected const CHUNK_ALIGNMENT_BYTES = 262144;

    /**
     * Batas waktu per potongan; unggahan video jauh lebih lama dari 30 detik bawaan.
     */
    protected const UPLOAD_TIMEOUT_SECONDS = 300;

    /**
     * Percobaan maksimum per potongan saat Google membalas 5xx atau koneksi putus.
     */
    protected const MAX_CHUNK_ATTEMPTS = 3;

    /**
     * Masa berlaku JWT assertion (maksimum yang diizinkan Google: 1 jam).
     */
    protected const JWT_LIFETIME_SECONDS = 3600;

    /**
     * Umur cache access token: 55 menit, memberi margin 5 menit sebelum
     * token benar-benar kedaluwarsa supaya request tidak memakai token mepet.
     */
    protected const TOKEN_CACHE_SECONDS = 3300;

    /**
     * Access token OAuth yang siap dipakai pada header Authorization,
     * ditukar dari refresh token akun Drive yang terhubung.
     *
     * Token di-cache 55 menit (margin 5 menit dari masa berlaku 1 jam Google)
     * sehingga penukaran hanya terjadi sekali per jam, bukan tiap request.
     */
    public function getAccessToken(): string
    {
        return Cache::remember(
            self::ACCESS_TOKEN_CACHE_KEY,
            self::TOKEN_CACHE_SECONDS,
            fn (): string => $this->requestAccessToken(),
        );
    }

    /**
     * Buang access token tersimpan, mis. setelah akun Drive diganti.
     */
    public static function forgetCachedAccessToken(): void
    {
        Cache::forget(self::ACCESS_TOKEN_CACHE_KEY);
    }

    /**
     * Unggah berkas kecil (dokumen Knowledge Repository: PDF, docx, dsb.)
     * ke folder Drive tertentu, mengembalikan file ID.
     *
     * Memakai multipart upload: metadata + isi berkas dalam satu request,
     * dan seluruh isi berkas dimuat ke memori. Google membatasi cara ini pada
     * berkas <= 5 MB. Untuk video CAPA gunakan {@see uploadFileResumable()}.
     */
    public function uploadFile(string $filePath, string $folderId, string $fileName, ?string $mimeType = null): string
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException("Berkas tidak ditemukan atau tidak dapat dibaca: {$filePath}");
        }

        $contents = file_get_contents($filePath);

        if ($contents === false) {
            throw new RuntimeException("Gagal membaca isi berkas: {$filePath}");
        }

        $metadata = [
            'name' => $fileName,
            'parents' => [$folderId],
        ];

        $boundary = 'cpsera'.bin2hex(random_bytes(16));
        // MIME dari pengunggah (UploadedFile::getMimeType()) lebih tepat daripada
        // menebak dari isi berkas; tebakan hanya dipakai sebagai cadangan.
        $mimeType = $mimeType ?: $this->guessMimeType($filePath);

        // Body multipart/related sesuai spesifikasi upload Drive v3:
        // bagian pertama metadata JSON, bagian kedua isi berkas mentah.
        $body = "--{$boundary}\r\n"
            ."Content-Type: application/json; charset=UTF-8\r\n\r\n"
            .$this->encodeJson($metadata)."\r\n"
            ."--{$boundary}\r\n"
            ."Content-Type: {$mimeType}\r\n\r\n"
            .$contents."\r\n"
            ."--{$boundary}--";

        $response = Http::withToken($this->getAccessToken())
            ->withBody($body, "multipart/related; boundary={$boundary}")
            ->post(self::UPLOAD_ENDPOINT.'?uploadType=multipart&fields=id');

        if ($response->failed()) {
            throw new RuntimeException(
                "Upload ke Google Drive gagal (HTTP {$response->status()}): ".$response->body()
            );
        }

        return $this->fileIdFromResponse($response);
    }

    /**
     * Unggah berkas besar (video penanganan CAPA) memakai protokol resumable
     * upload Drive v3, mengembalikan file ID.
     *
     * Berkas dibaca per potongan dengan stream, sehingga penggunaan memori
     * tetap sebesar satu potongan berapa pun ukuran berkasnya — penting pada
     * shared hosting dengan memory_limit ketat.
     *
     * @param  int|null  $chunkBytes  Besar potongan; wajib kelipatan 256 KiB.
     * @param  string|null  $mimeType  MIME dari pengunggah; bila null ditebak dari isi berkas.
     *
     * @see https://developers.google.com/workspace/drive/api/guides/manage-uploads#resumable
     */
    public function uploadFileResumable(string $filePath, string $folderId, string $fileName, ?int $chunkBytes = null, ?string $mimeType = null): string
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException("Berkas tidak ditemukan atau tidak dapat dibaca: {$filePath}");
        }

        $size = filesize($filePath);

        if ($size === false || $size === 0) {
            throw new RuntimeException("Berkas kosong atau ukurannya tidak terbaca: {$filePath}");
        }

        $chunkBytes ??= self::RESUMABLE_CHUNK_BYTES;

        if ($chunkBytes <= 0 || $chunkBytes % self::CHUNK_ALIGNMENT_BYTES !== 0) {
            throw new RuntimeException(
                "Besar potongan unggahan harus kelipatan 256 KiB, diberikan: {$chunkBytes} bita."
            );
        }

        $sessionUri = $this->openResumableSession(
            $fileName,
            $folderId,
            $mimeType ?: $this->guessMimeType($filePath),
            $size
        );

        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Gagal membuka berkas untuk diunggah: {$filePath}");
        }

        try {
            $offset = 0;
            $stalls = 0;

            while ($offset < $size) {
                $chunk = fread($handle, $chunkBytes);

                if ($chunk === false || $chunk === '') {
                    throw new RuntimeException(
                        "Gagal membaca potongan berkas pada posisi {$offset}: {$filePath}"
                    );
                }

                $length = strlen($chunk);
                $response = $this->uploadChunk($sessionUri, $chunk, $offset, $offset + $length - 1, $size);
                $status = $response->status();

                // 200/201 hanya muncul pada potongan terakhir, memuat metadata berkas.
                if ($status === 200 || $status === 201) {
                    return $this->fileIdFromResponse($response);
                }

                // 308 "Resume Incomplete": Google menunggu potongan berikutnya.
                if ($status !== 308) {
                    throw new RuntimeException(
                        "Unggahan potongan ke Google Drive gagal (HTTP {$status}): ".$response->body()
                    );
                }

                $confirmedOffset = $this->confirmedOffset($response, $offset + $length);

                // Tanpa kemajuan posisi, pengiriman ulang diulang terbatas agar
                // tidak berputar selamanya saat Google terus menolak maju.
                if ($confirmedOffset <= $offset) {
                    $stalls++;

                    if ($stalls >= self::MAX_CHUNK_ATTEMPTS) {
                        throw new RuntimeException(
                            "Unggahan resumable tidak maju dari posisi {$offset} bita setelah {$stalls} percobaan."
                        );
                    }
                } else {
                    $stalls = 0;
                }

                // Selaraskan posisi baca bila Google menerima lebih sedikit dari yang dikirim.
                if ($confirmedOffset !== $offset + $length && fseek($handle, $confirmedOffset) !== 0) {
                    throw new RuntimeException(
                        "Gagal melanjutkan unggahan dari posisi {$confirmedOffset}: {$filePath}"
                    );
                }

                $offset = $confirmedOffset;
            }
        } finally {
            fclose($handle);
        }

        throw new RuntimeException(
            'Seluruh potongan terkirim tetapi Google Drive tidak mengembalikan file ID.'
        );
    }

    /**
     * Beri izin baca publik ("siapa saja yang memiliki link") pada sebuah berkas.
     */
    public function setPublicPermission(string $fileId): void
    {
        $response = Http::withToken($this->getAccessToken())
            ->asJson()
            ->post(self::FILES_ENDPOINT.'/'.rawurlencode($fileId).'/permissions', [
                'role' => 'reader',
                'type' => 'anyone',
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "Gagal menetapkan izin publik Google Drive (HTTP {$response->status()}): ".$response->body()
            );
        }
    }

    /**
     * Hapus berkas dari Drive. Berkas yang memang sudah tidak ada dianggap
     * berhasil (idempoten), sehingga pembersihan ulang tidak menimbulkan galat.
     */
    public function deleteFile(string $fileId): void
    {
        $response = Http::withToken($this->getAccessToken())
            ->delete(self::FILES_ENDPOINT.'/'.rawurlencode($fileId));

        if ($response->status() === 404) {
            return;
        }

        if ($response->failed()) {
            throw new RuntimeException(
                "Gagal menghapus berkas Google Drive (HTTP {$response->status()}): ".$response->body()
            );
        }
    }

    /**
     * URL preview Drive yang dapat disematkan pada iframe pemutar video.
     */
    public function getPreviewUrl(string $fileId): string
    {
        return "https://drive.google.com/file/d/{$fileId}/preview";
    }

    /**
     * URL untuk membuka/mengunduh berkas di web Drive.
     */
    public function getViewUrl(string $fileId): string
    {
        return "https://drive.google.com/file/d/{$fileId}/view";
    }

    /**
     * Apakah nilai kolom berisi file ID Drive, bukan path berkas lokal warisan
     * (mis. "/storage/videos/mandatory/x.mp4") atau URL penuh.
     *
     * File ID Drive hanya terdiri dari huruf, angka, tanda hubung, dan garis bawah.
     */
    public static function isDriveFileId(?string $value): bool
    {
        if (blank($value)) {
            return false;
        }

        return preg_match('/^[A-Za-z0-9_-]{20,}$/', $value) === 1;
    }

    /**
     * File ID Drive dari file ID mentah atau tautan Drive (…/file/d/{id}/…, open?id=, uc?id=).
     * Null bila nilainya bukan berkas Drive.
     */
    public static function fileIdFrom(?string $value): ?string
    {
        if (static::isDriveFileId($value)) {
            return $value;
        }

        if (blank($value) || preg_match('~(?:drive|docs)\.google\.com/(?:file/d/|(?:open|uc)\?(?:.*&)?id=)([A-Za-z0-9_-]{20,})~', $value, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Susun JWT RS256 dan tandatangani dengan private key service account.
     *
     * @deprecated Tidak lagi dipakai getAccessToken(): service account tidak
     * memiliki kuota penyimpanan sehingga tidak bisa memiliki berkas di My Drive.
     * Dipertahankan agar mudah dihidupkan kembali bila kelak memakai Shared Drive
     * atau domain-wide delegation.
     *
     * @param  array<string, mixed>|null  $credentials  Isi kredensial; default membaca berkas dari konfigurasi.
     */
    public function createSignedJwt(?array $credentials = null, ?int $issuedAt = null): string
    {
        $credentials ??= $this->credentials();
        $issuedAt ??= time();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $claims = [
            'iss' => $credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => $credentials['token_uri'] ?? self::TOKEN_ENDPOINT,
            'iat' => $issuedAt,
            'exp' => $issuedAt + self::JWT_LIFETIME_SECONDS,
        ];

        $signingInput = $this->base64UrlEncode($this->encodeJson($header))
            .'.'.$this->base64UrlEncode($this->encodeJson($claims));

        $privateKey = openssl_pkey_get_private($credentials['private_key']);

        if ($privateKey === false) {
            throw new RuntimeException(
                'Private key pada kredensial service account tidak valid: '.openssl_error_string()
            );
        }

        $signature = '';

        if (! openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException(
                'Gagal menandatangani JWT Google Drive: '.openssl_error_string()
            );
        }

        return $signingInput.'.'.$this->base64UrlEncode($signature);
    }

    /**
     * Buka sesi unggahan resumable, mengembalikan session URI dari header Location.
     */
    protected function openResumableSession(string $fileName, string $folderId, string $mimeType, int $size): string
    {
        $response = Http::withToken($this->getAccessToken())
            ->withHeaders([
                'X-Upload-Content-Type' => $mimeType,
                'X-Upload-Content-Length' => (string) $size,
            ])
            ->asJson()
            ->post(self::UPLOAD_ENDPOINT.'?uploadType=resumable&fields=id', [
                'name' => $fileName,
                'parents' => [$folderId],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "Gagal membuka sesi unggahan Google Drive (HTTP {$response->status()}): ".$response->body()
            );
        }

        $sessionUri = $response->header('Location');

        if ($sessionUri === '') {
            throw new RuntimeException(
                'Respons sesi unggahan Google Drive tidak memuat header Location.'
            );
        }

        return $sessionUri;
    }

    /**
     * Kirim satu potongan ke session URI, dengan percobaan ulang untuk
     * galat sementara (5xx / koneksi putus) sesuai anjuran Google.
     */
    protected function uploadChunk(string $sessionUri, string $chunk, int $start, int $end, int $total): Response
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                $response = Http::withToken($this->getAccessToken())
                    // 308 pada protokol resumable adalah status kendali, bukan redirect.
                    ->withOptions(['allow_redirects' => false])
                    ->timeout(self::UPLOAD_TIMEOUT_SECONDS)
                    ->withHeaders(['Content-Range' => "bytes {$start}-{$end}/{$total}"])
                    ->withBody($chunk, 'application/octet-stream')
                    ->put($sessionUri);
            } catch (ConnectionException $exception) {
                if ($attempt >= self::MAX_CHUNK_ATTEMPTS) {
                    throw new RuntimeException(
                        "Koneksi ke Google Drive terputus saat mengunggah bita {$start}-{$end}.",
                        previous: $exception
                    );
                }

                sleep($attempt);

                continue;
            }

            if ($response->serverError() && $attempt < self::MAX_CHUNK_ATTEMPTS) {
                sleep($attempt);

                continue;
            }

            return $response;
        }
    }

    /**
     * Posisi bita berikutnya yang diminta Google, dibaca dari header Range
     * pada respons 308 (mis. "bytes=0-262143" berarti lanjut dari 262144).
     */
    protected function confirmedOffset(Response $response, int $fallback): int
    {
        $range = $response->header('Range');

        if (preg_match('/bytes=0-(\d+)/', $range, $matches) === 1) {
            return ((int) $matches[1]) + 1;
        }

        return $fallback;
    }

    protected function fileIdFromResponse(Response $response): string
    {
        $fileId = $response->json('id');

        if (! is_string($fileId) || $fileId === '') {
            throw new RuntimeException('Respons upload Google Drive tidak memuat file ID.');
        }

        return $fileId;
    }

    /**
     * Tukar refresh token akun yang terhubung menjadi access token baru.
     */
    protected function requestAccessToken(): string
    {
        $connection = GoogleDriveToken::active();

        if ($connection === null) {
            throw new RuntimeException(
                'Google Drive belum terhubung, silakan connect di /admin/google-drive/connect'
            );
        }

        foreach (['client_id', 'client_secret'] as $key) {
            if (blank(config("services.google_drive.{$key}"))) {
                throw new RuntimeException(
                    'GOOGLE_DRIVE_'.strtoupper($key).' belum diisi pada berkas .env.'
                );
            }
        }

        $response = Http::asForm()->post(self::TOKEN_ENDPOINT, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $connection->plainRefreshToken(),
            'client_id' => config('services.google_drive.client_id'),
            'client_secret' => config('services.google_drive.client_secret'),
        ]);

        if ($response->status() === 400 && $response->json('error') === 'invalid_grant') {
            throw new RuntimeException(
                'Refresh token Google Drive ditolak (kedaluwarsa atau dicabut). '
                .'Hubungkan ulang di /admin/google-drive/connect'
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                "Gagal menukar refresh token dengan access token Google (HTTP {$response->status()}): ".$response->body()
            );
        }

        $accessToken = $response->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Respons token Google tidak memuat access_token.');
        }

        return $accessToken;
    }

    /**
     * Baca & validasi berkas kredensial service account JSON.
     *
     * @deprecated Menyertai createSignedJwt(); alur aktif memakai OAuth pengguna.
     *
     * @return array<string, mixed>
     */
    protected function credentials(): array
    {
        $path = config('services.google_drive.credentials_path');

        if (! is_string($path) || $path === '') {
            throw new RuntimeException(
                'GOOGLE_DRIVE_CREDENTIALS_PATH belum diisi pada berkas .env.'
            );
        }

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException(
                "Berkas kredensial Google Drive tidak ditemukan atau tidak dapat dibaca: {$path}"
            );
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            throw new RuntimeException(
                "Berkas kredensial Google Drive bukan JSON yang valid: {$path}"
            );
        }

        foreach (['client_email', 'private_key'] as $key) {
            if (! isset($decoded[$key]) || ! is_string($decoded[$key]) || $decoded[$key] === '') {
                throw new RuntimeException(
                    "Kredensial Google Drive tidak memuat kunci [{$key}] yang valid: {$path}"
                );
            }
        }

        return $decoded;
    }

    protected function guessMimeType(string $filePath): string
    {
        $mimeType = function_exists('mime_content_type') ? @mime_content_type($filePath) : false;

        return is_string($mimeType) && $mimeType !== '' ? $mimeType : 'application/octet-stream';
    }

    /**
     * @param  array<string, mixed>  $value
     */
    protected function encodeJson(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
