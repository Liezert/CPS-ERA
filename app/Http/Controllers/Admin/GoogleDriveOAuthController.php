<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleDriveToken;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Menghubungkan aplikasi ke satu akun Google Drive lewat OAuth 2.0
 * (authorization code + refresh token).
 *
 * Service account tidak dipakai lagi karena tidak memiliki kuota penyimpanan
 * sendiri sehingga tidak dapat memiliki berkas di My Drive biasa.
 */
class GoogleDriveOAuthController extends Controller
{
    public const AUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';

    public const USERINFO_ENDPOINT = 'https://www.googleapis.com/oauth2/v3/userinfo';

    protected const STATE_SESSION_KEY = 'google_drive_oauth_state';

    /**
     * Alihkan admin ke layar consent Google.
     */
    public function connect(Request $request): RedirectResponse
    {
        $clientId = config('services.google_drive.client_id');
        $redirectUri = config('services.google_drive.redirect_uri');

        if (blank($clientId) || blank(config('services.google_drive.client_secret')) || blank($redirectUri)) {
            return redirect()
                ->route('filament.admin.pages.google-drive')
                ->with('gdrive_error', 'GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET, dan GOOGLE_DRIVE_REDIRECT_URI wajib diisi pada .env sebelum menghubungkan Drive.');
        }

        // State mengikat balasan Google ke sesi ini (proteksi CSRF alur OAuth).
        $state = Str::random(40);
        $request->session()->put(self::STATE_SESSION_KEY, $state);

        return redirect()->away(self::AUTH_ENDPOINT.'?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => GoogleDriveService::SCOPE.' https://www.googleapis.com/auth/userinfo.email',
            // offline + consent: wajib agar Google selalu mengirim refresh_token,
            // bukan hanya pada consent pertama kali.
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]));
    }

    /**
     * Terima authorization code, tukar menjadi token, simpan refresh token terenkripsi.
     */
    public function callback(Request $request): RedirectResponse
    {
        $halaman = redirect()->route('filament.admin.pages.google-drive');
        $stateTersimpan = $request->session()->pull(self::STATE_SESSION_KEY);

        if (blank($stateTersimpan) || ! hash_equals($stateTersimpan, (string) $request->query('state'))) {
            return $halaman->with('gdrive_error', 'Parameter state tidak cocok. Ulangi proses connect dari halaman ini.');
        }

        if ($request->filled('error')) {
            return $halaman->with('gdrive_error', 'Google menolak atau membatalkan izin: '.$request->query('error'));
        }

        $code = (string) $request->query('code');

        if (blank($code)) {
            return $halaman->with('gdrive_error', 'Google tidak mengirim authorization code.');
        }

        $response = Http::asForm()->post(GoogleDriveService::TOKEN_ENDPOINT, [
            'code' => $code,
            'client_id' => config('services.google_drive.client_id'),
            'client_secret' => config('services.google_drive.client_secret'),
            'redirect_uri' => config('services.google_drive.redirect_uri'),
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            // Badan respons memuat alasan penolakan Google, bukan token.
            Log::warning('Penukaran authorization code Google Drive gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $halaman->with('gdrive_error', "Penukaran kode gagal (HTTP {$response->status()}): ".$response->json('error_description', $response->json('error', 'galat tidak diketahui')));
        }

        $refreshToken = $response->json('refresh_token');

        if (! is_string($refreshToken) || $refreshToken === '') {
            return $halaman->with('gdrive_error', 'Google tidak mengirim refresh_token. Cabut akses aplikasi di myaccount.google.com/permissions lalu ulangi connect.');
        }

        $email = $this->fetchAccountEmail($response->json('access_token'));

        // Satu akun Drive dipakai bersama: koneksi lama digantikan seluruhnya.
        GoogleDriveToken::query()->delete();

        GoogleDriveToken::create([
            'refresh_token' => Crypt::encryptString($refreshToken),
            'connected_email' => $email,
            'connected_by' => $request->user()?->id,
            'connected_at' => now(),
        ]);

        // Token lama milik koneksi sebelumnya tidak boleh ikut terpakai.
        GoogleDriveService::forgetCachedAccessToken();

        return $halaman->with(
            'gdrive_status',
            'Google Drive berhasil terhubung'.($email ? ' sebagai '.$email : '').'.'
        );
    }

    /**
     * Email akun yang memberi consent; hanya untuk ditampilkan di halaman admin.
     */
    protected function fetchAccountEmail(mixed $accessToken): ?string
    {
        if (! is_string($accessToken) || $accessToken === '') {
            return null;
        }

        $response = Http::withToken($accessToken)->get(self::USERINFO_ENDPOINT);

        $email = $response->successful() ? $response->json('email') : null;

        return is_string($email) ? $email : null;
    }
}
