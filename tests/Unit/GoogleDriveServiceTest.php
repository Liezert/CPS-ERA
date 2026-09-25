<?php

namespace Tests\Unit;

use App\Models\GoogleDriveToken;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * Pengujian alur token & unggahan GoogleDriveService.
 * Tidak ada permintaan ke API Google sungguhan: seluruh HTTP di-fake dan
 * kunci RSA (untuk jalur service account lama) dibuat sesaat.
 */
class GoogleDriveServiceTest extends TestCase
{
    use RefreshDatabase;

    protected string $credentialsPath;

    /** @var array<string, string> */
    protected array $keyPair;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyPair = $this->generateKeyPair();
        $this->credentialsPath = tempnam(sys_get_temp_dir(), 'gdrive').'.json';

        file_put_contents($this->credentialsPath, json_encode([
            'type' => 'service_account',
            'client_email' => 'cps-era@contoh.iam.gserviceaccount.com',
            'private_key' => $this->keyPair['private'],
            'token_uri' => GoogleDriveService::TOKEN_ENDPOINT,
        ]));

        config()->set('services.google_drive.credentials_path', $this->credentialsPath);
        config()->set('services.google_drive.client_id', 'klien-uji.apps.googleusercontent.com');
        config()->set('services.google_drive.client_secret', 'rahasia-klien-uji');

        $this->connectDrive();
    }

    /**
     * Simulasikan akun Drive yang sudah melewati layar consent.
     */
    protected function connectDrive(string $refreshToken = 'refresh-token-uji'): GoogleDriveToken
    {
        return GoogleDriveToken::create([
            'refresh_token' => Crypt::encryptString($refreshToken),
            'connected_email' => 'drive-cps@gmail.com',
            'connected_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        if (is_file($this->credentialsPath)) {
            unlink($this->credentialsPath);
        }

        parent::tearDown();
    }

    public function test_jwt_memiliki_struktur_dan_klaim_service_account(): void
    {
        $issuedAt = 1_760_000_000;

        $jwt = (new GoogleDriveService)->createSignedJwt(issuedAt: $issuedAt);

        $segments = explode('.', $jwt);
        $this->assertCount(3, $segments, 'JWT terdiri dari header, payload, dan signature');

        $header = $this->decodeSegment($segments[0]);
        $this->assertSame('RS256', $header['alg']);
        $this->assertSame('JWT', $header['typ']);

        $claims = $this->decodeSegment($segments[1]);
        $this->assertSame('cps-era@contoh.iam.gserviceaccount.com', $claims['iss']);
        // Least privilege: hanya berkas yang dibuat service account ini.
        $this->assertSame('https://www.googleapis.com/auth/drive.file', $claims['scope']);
        $this->assertSame(GoogleDriveService::TOKEN_ENDPOINT, $claims['aud']);
        $this->assertSame($issuedAt, $claims['iat']);
        // Google menolak assertion dengan masa berlaku lebih dari 1 jam.
        $this->assertSame($issuedAt + 3600, $claims['exp']);

        // Base64url: tanpa padding '=' dan tanpa karakter '+' atau '/'.
        foreach ($segments as $segment) {
            $this->assertDoesNotMatchRegularExpression('/[+\/=]/', $segment);
        }
    }

    public function test_signature_jwt_valid_terhadap_public_key_kredensial(): void
    {
        $jwt = (new GoogleDriveService)->createSignedJwt();

        [$header, $payload, $signature] = explode('.', $jwt);

        $verified = openssl_verify(
            "{$header}.{$payload}",
            $this->base64UrlDecode($signature),
            $this->keyPair['public'],
            OPENSSL_ALGO_SHA256
        );

        $this->assertSame(1, $verified, 'Signature harus terverifikasi dengan public key pasangannya');
    }

    public function test_signature_ditolak_bila_payload_diubah(): void
    {
        $jwt = (new GoogleDriveService)->createSignedJwt();

        [$header, $payload, $signature] = explode('.', $jwt);
        $payloadPalsu = rtrim(strtr(base64_encode(json_encode([
            'iss' => 'penyusup@contoh.iam.gserviceaccount.com',
            'scope' => 'https://www.googleapis.com/auth/drive',
        ])), '+/', '-_'), '=');

        $verified = openssl_verify(
            "{$header}.{$payloadPalsu}",
            $this->base64UrlDecode($signature),
            $this->keyPair['public'],
            OPENSSL_ALGO_SHA256
        );

        $this->assertSame(0, $verified, 'Payload yang diubah harus gagal verifikasi');
    }

    public function test_access_token_ditukar_dari_refresh_token_dan_disimpan_di_cache(): void
    {
        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response([
                'access_token' => 'token-akses-uji',
                'expires_in' => 3600,
            ]),
        ]);

        $service = new GoogleDriveService;

        $this->assertSame('token-akses-uji', $service->getAccessToken());
        // Panggilan kedua dilayani cache, bukan permintaan token baru.
        $this->assertSame('token-akses-uji', $service->getAccessToken());

        $this->assertSame(
            'token-akses-uji',
            Cache::get(GoogleDriveService::ACCESS_TOKEN_CACHE_KEY)
        );

        Http::assertSentCount(1);
        Http::assertSent(function ($request): bool {
            $this->assertSame('refresh_token', $request['grant_type']);
            // Refresh token dikirim dalam bentuk terbaca, bukan ciphertext.
            $this->assertSame('refresh-token-uji', $request['refresh_token']);
            $this->assertSame('klien-uji.apps.googleusercontent.com', $request['client_id']);
            $this->assertSame('rahasia-klien-uji', $request['client_secret']);

            return true;
        });
    }

    public function test_cache_token_dapat_dibuang_saat_akun_drive_diganti(): void
    {
        Cache::put(GoogleDriveService::ACCESS_TOKEN_CACHE_KEY, 'token-akses-uji', 60);

        GoogleDriveService::forgetCachedAccessToken();

        $this->assertNull(Cache::get(GoogleDriveService::ACCESS_TOKEN_CACHE_KEY));
    }

    public function test_drive_yang_belum_terhubung_memberi_pesan_yang_jelas(): void
    {
        GoogleDriveToken::query()->delete();
        Http::fake();

        $service = new GoogleDriveService;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Google Drive belum terhubung, silakan connect di /admin/google-drive/connect');

        $service->getAccessToken();
    }

    public function test_refresh_token_yang_dicabut_meminta_hubungkan_ulang(): void
    {
        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Hubungkan ulang di /admin/google-drive/connect');

        (new GoogleDriveService)->getAccessToken();
    }

    public function test_kegagalan_endpoint_token_lainnya_dilaporkan_sebagai_exception(): void
    {
        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response(['error' => 'server_error'], 500),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        (new GoogleDriveService)->getAccessToken();
    }

    public function test_client_credentials_yang_kosong_dilaporkan(): void
    {
        config()->set('services.google_drive.client_secret', null);
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GOOGLE_DRIVE_CLIENT_SECRET');

        (new GoogleDriveService)->getAccessToken();
    }

    public function test_upload_file_mengirim_multipart_related_dan_mengembalikan_file_id(): void
    {
        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response(['access_token' => 'token-akses-uji']),
            'www.googleapis.com/upload/drive/v3/files*' => Http::response(['id' => '1AbCdEfGh']),
        ]);

        $berkas = tempnam(sys_get_temp_dir(), 'gdrive-upload');
        file_put_contents($berkas, 'isi berkas uji');

        try {
            $fileId = (new GoogleDriveService)->uploadFile($berkas, 'folder-ba-123', 'BA-2026-0001.mp4');
        } finally {
            unlink($berkas);
        }

        $this->assertSame('1AbCdEfGh', $fileId);

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/upload/drive/v3/files')) {
                return false;
            }

            $this->assertStringContainsString('uploadType=multipart', $request->url());
            $this->assertStringStartsWith('multipart/related; boundary=', $request->header('Content-Type')[0]);
            $this->assertSame('Bearer token-akses-uji', $request->header('Authorization')[0]);
            $this->assertStringContainsString('"name":"BA-2026-0001.mp4"', $request->body());
            $this->assertStringContainsString('"parents":["folder-ba-123"]', $request->body());
            $this->assertStringContainsString('isi berkas uji', $request->body());

            return true;
        });
    }

    public function test_upload_file_menolak_berkas_yang_tidak_ada(): void
    {
        Http::fake();

        $this->expectException(RuntimeException::class);

        (new GoogleDriveService)->uploadFile(
            sys_get_temp_dir().'/berkas-tidak-ada-'.uniqid().'.mp4',
            'folder-ba-123',
            'tidak-ada.mp4'
        );
    }

    public function test_upload_resumable_mengirim_berkas_per_potongan_dan_mengembalikan_file_id(): void
    {
        $chunkBytes = 262144; // 256 KiB, potongan terkecil yang diizinkan Google.
        $ukuran = $chunkBytes * 2 + 1024; // 3 potongan: dua penuh, satu sisa.
        $isi = str_repeat('v', $ukuran);

        $sessionUri = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&upload_id=sesi-uji';
        $potongan = [];

        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response(['access_token' => 'token-akses-uji']),
            'www.googleapis.com/upload/drive/v3/files*' => function ($request) use (&$potongan, $sessionUri, $ukuran) {
                if ($request->method() === 'POST') {
                    return Http::response('', 200, ['Location' => $sessionUri]);
                }

                [$rentang] = $request->header('Content-Range');
                $potongan[] = $rentang;
                preg_match('/bytes (\d+)-(\d+)\//', $rentang, $cocok);
                $akhir = (int) $cocok[2];

                return $akhir === $ukuran - 1
                    ? Http::response(['id' => '1VideoCapa'])
                    : Http::response('', 308, ['Range' => "bytes=0-{$akhir}"]);
            },
        ]);

        $berkas = tempnam(sys_get_temp_dir(), 'gdrive-video');
        file_put_contents($berkas, $isi);

        try {
            $fileId = (new GoogleDriveService)->uploadFileResumable(
                $berkas,
                'folder-ba-123',
                'BA-2026-0001.mp4',
                $chunkBytes
            );
        } finally {
            unlink($berkas);
        }

        $this->assertSame('1VideoCapa', $fileId);
        $this->assertSame([
            'bytes 0-262143/'.$ukuran,
            'bytes 262144-524287/'.$ukuran,
            'bytes 524288-'.($ukuran - 1).'/'.$ukuran,
        ], $potongan, 'Potongan harus berurutan & menutup seluruh berkas');

        Http::assertSent(function ($request) use ($ukuran): bool {
            if ($request->method() !== 'POST' || ! str_contains($request->url(), '/upload/drive/v3/files')) {
                return false;
            }

            $this->assertStringContainsString('uploadType=resumable', $request->url());
            $this->assertSame((string) $ukuran, $request->header('X-Upload-Content-Length')[0]);
            $this->assertSame(['name' => 'BA-2026-0001.mp4', 'parents' => ['folder-ba-123']], $request->data());

            return true;
        });
    }

    public function test_upload_resumable_melanjutkan_dari_posisi_yang_dikonfirmasi_google(): void
    {
        $chunkBytes = 262144;
        $ukuran = $chunkBytes * 2;
        $rentangTerkirim = [];

        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response(['access_token' => 'token-akses-uji']),
            'www.googleapis.com/upload/drive/v3/files*' => function ($request) use (&$rentangTerkirim, $ukuran) {
                if ($request->method() === 'POST') {
                    return Http::response('', 200, [
                        'Location' => 'https://www.googleapis.com/upload/drive/v3/files?upload_id=sesi-uji',
                    ]);
                }

                [$rentang] = $request->header('Content-Range');
                $rentangTerkirim[] = $rentang;

                // Potongan pertama hanya separuh diterima Google.
                if (count($rentangTerkirim) === 1) {
                    return Http::response('', 308, ['Range' => 'bytes=0-131071']);
                }

                preg_match('/bytes \d+-(\d+)\//', $rentang, $cocok);
                $akhir = (int) $cocok[1];

                return $akhir === $ukuran - 1
                    ? Http::response(['id' => '1VideoLanjut'])
                    : Http::response('', 308, ['Range' => "bytes=0-{$akhir}"]);
            },
        ]);

        $berkas = tempnam(sys_get_temp_dir(), 'gdrive-video');
        file_put_contents($berkas, str_repeat('v', $ukuran));

        try {
            $fileId = (new GoogleDriveService)->uploadFileResumable(
                $berkas,
                'folder-ba-123',
                'BA-2026-0002.mp4',
                $chunkBytes
            );
        } finally {
            unlink($berkas);
        }

        $this->assertSame('1VideoLanjut', $fileId);
        // Potongan kedua dimulai dari 131072, bukan 262144.
        $this->assertSame('bytes 131072-393215/'.$ukuran, $rentangTerkirim[1]);
    }

    public function test_upload_resumable_berhenti_bila_posisi_tidak_pernah_maju(): void
    {
        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response(['access_token' => 'token-akses-uji']),
            'www.googleapis.com/upload/drive/v3/files*' => function ($request) {
                // Google selalu mengonfirmasi 0 bita: unggahan mandek.
                return $request->method() === 'POST'
                    ? Http::response('', 200, ['Location' => 'https://www.googleapis.com/upload/drive/v3/files?upload_id=sesi-uji'])
                    : Http::response('', 308, ['Range' => 'bytes=0-0']);
            },
        ]);

        $berkas = tempnam(sys_get_temp_dir(), 'gdrive-video');
        file_put_contents($berkas, str_repeat('v', 1024));

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('tidak maju');

            (new GoogleDriveService)->uploadFileResumable($berkas, 'folder-ba-123', 'video.mp4');
        } finally {
            unlink($berkas);
        }
    }

    public function test_upload_resumable_menolak_potongan_bukan_kelipatan_256_kib(): void
    {
        Http::fake();

        $berkas = tempnam(sys_get_temp_dir(), 'gdrive-video');
        file_put_contents($berkas, 'isi');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('kelipatan 256 KiB');

            (new GoogleDriveService)->uploadFileResumable($berkas, 'folder-ba-123', 'video.mp4', 100000);
        } finally {
            unlink($berkas);
        }
    }

    public function test_upload_resumable_melaporkan_kegagalan_potongan(): void
    {
        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response(['access_token' => 'token-akses-uji']),
            'www.googleapis.com/upload/drive/v3/files*' => function ($request) {
                return $request->method() === 'POST'
                    ? Http::response('', 200, ['Location' => 'https://www.googleapis.com/upload/drive/v3/files?upload_id=sesi-uji'])
                    : Http::response(['error' => 'notFound'], 404);
            },
        ]);

        $berkas = tempnam(sys_get_temp_dir(), 'gdrive-video');
        file_put_contents($berkas, str_repeat('v', 1024));

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('HTTP 404');

            (new GoogleDriveService)->uploadFileResumable($berkas, 'folder-hilang', 'video.mp4');
        } finally {
            unlink($berkas);
        }
    }

    public function test_upload_resumable_gagal_bila_sesi_tidak_memberi_location(): void
    {
        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response(['access_token' => 'token-akses-uji']),
            'www.googleapis.com/upload/drive/v3/files*' => Http::response('', 200),
        ]);

        $berkas = tempnam(sys_get_temp_dir(), 'gdrive-video');
        file_put_contents($berkas, str_repeat('v', 1024));

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('header Location');

            (new GoogleDriveService)->uploadFileResumable($berkas, 'folder-ba-123', 'video.mp4');
        } finally {
            unlink($berkas);
        }
    }

    public function test_set_public_permission_mengirim_role_reader_untuk_anyone(): void
    {
        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response(['access_token' => 'token-akses-uji']),
            'www.googleapis.com/drive/v3/files/*' => Http::response(['id' => 'anyoneWithLink']),
        ]);

        (new GoogleDriveService)->setPublicPermission('1AbCdEfGh');

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/drive/v3/files/')) {
                return false;
            }

            $this->assertStringEndsWith('/files/1AbCdEfGh/permissions', $request->url());
            $this->assertSame(['role' => 'reader', 'type' => 'anyone'], $request->data());

            return true;
        });
    }

    public function test_preview_url_memakai_format_embed_drive(): void
    {
        $this->assertSame(
            'https://drive.google.com/file/d/1AbCdEfGh/preview',
            (new GoogleDriveService)->getPreviewUrl('1AbCdEfGh')
        );
    }

    public function test_kredensial_yang_hilang_dilaporkan_dengan_jelas(): void
    {
        config()->set('services.google_drive.credentials_path', null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GOOGLE_DRIVE_CREDENTIALS_PATH');

        (new GoogleDriveService)->createSignedJwt();
    }

    /**
     * @return array<string, string>
     */
    protected function generateKeyPair(): array
    {
        // openssl.cnf bawaan tidak selalu tersedia (mis. PHP di Windows),
        // jadi pembuatan kunci memakai konfigurasi minimal sementara.
        $config = tempnam(sys_get_temp_dir(), 'openssl').'.cnf';
        file_put_contents($config, "[ req ]\ndistinguished_name = req_dn\n[ req_dn ]\n");

        try {
            $resource = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'config' => $config,
            ]);

            $this->assertNotFalse($resource, 'Gagal membuat pasangan kunci RSA untuk pengujian');

            openssl_pkey_export($resource, $privateKey, null, ['config' => $config]);
        } finally {
            unlink($config);
        }

        return [
            'private' => $privateKey,
            'public' => openssl_pkey_get_details($resource)['key'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeSegment(string $segment): array
    {
        return json_decode($this->base64UrlDecode($segment), true);
    }

    public function test_file_id_diambil_dari_id_mentah_maupun_tautan_drive(): void
    {
        $id = '1IBe-loIfrBnJiVUG7SN__45M40MAnz-0';

        foreach ([
            $id,
            "https://drive.google.com/file/d/{$id}/preview",
            "https://drive.google.com/file/d/{$id}/view?usp=sharing",
            "https://drive.google.com/open?id={$id}",
            "https://drive.google.com/uc?export=download&id={$id}",
            "https://docs.google.com/file/d/{$id}/edit",
        ] as $value) {
            $this->assertSame($id, GoogleDriveService::fileIdFrom($value), $value);
        }

        foreach ([null, '', 'https://youtube.com/watch?v=abc', '/storage/videos/x.mp4', 'learning-materials/files/a.mp4'] as $value) {
            $this->assertNull(GoogleDriveService::fileIdFrom($value), (string) $value);
        }
    }

    protected function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4));
    }
}
