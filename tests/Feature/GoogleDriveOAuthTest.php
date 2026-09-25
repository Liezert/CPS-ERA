<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\GoogleDriveOAuthController;
use App\Models\Division;
use App\Models\GoogleDriveToken;
use App\Models\User;
use App\Services\GoogleDriveService;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Alur consent OAuth Google Drive: hanya admin, terlindung parameter state,
 * dan refresh token tersimpan dalam bentuk terenkripsi.
 */
class GoogleDriveOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);

        $division = Division::where('name', 'Engineering')->firstOrFail();

        $this->admin = User::factory()->create(['division_id' => $division->id]);
        $this->admin->assignRole('admin');

        $this->employee = User::factory()->create(['division_id' => $division->id]);
        $this->employee->assignRole('employee');

        config()->set('services.google_drive.client_id', 'klien-uji.apps.googleusercontent.com');
        config()->set('services.google_drive.client_secret', 'rahasia-klien-uji');
        config()->set('services.google_drive.redirect_uri', 'http://localhost:8000/admin/google-drive/callback');
    }

    public function test_connect_mengalihkan_ke_layar_consent_google_dengan_parameter_wajib(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/google-drive/connect');

        $response->assertRedirect();
        $tujuan = $response->headers->get('Location');

        $this->assertStringStartsWith(GoogleDriveOAuthController::AUTH_ENDPOINT, $tujuan);

        parse_str(parse_url($tujuan, PHP_URL_QUERY), $query);

        $this->assertSame('klien-uji.apps.googleusercontent.com', $query['client_id']);
        $this->assertSame('http://localhost:8000/admin/google-drive/callback', $query['redirect_uri']);
        $this->assertSame('code', $query['response_type']);
        // offline + consent: Google selalu mengirim refresh_token, bukan hanya sekali.
        $this->assertSame('offline', $query['access_type']);
        $this->assertSame('consent', $query['prompt']);
        $this->assertStringContainsString(GoogleDriveService::SCOPE, $query['scope']);
        $this->assertNotEmpty($query['state']);
        $this->assertSame($query['state'], session('google_drive_oauth_state'));
    }

    public function test_employee_tidak_boleh_mengakses_alur_connect(): void
    {
        $this->actingAs($this->employee)->get('/admin/google-drive/connect')->assertForbidden();
        $this->actingAs($this->employee)->get('/admin/google-drive/callback')->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/google-drive/connect')->assertRedirect('/login');
    }

    public function test_callback_menyimpan_refresh_token_terenkripsi(): void
    {
        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response([
                'access_token' => 'token-akses-uji',
                'refresh_token' => 'refresh-token-uji',
                'expires_in' => 3600,
            ]),
            GoogleDriveOAuthController::USERINFO_ENDPOINT => Http::response(['email' => 'drive-cps@gmail.com']),
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['google_drive_oauth_state' => 'state-uji'])
            ->get('/admin/google-drive/callback?code=kode-otorisasi&state=state-uji');

        $response->assertRedirect(route('filament.admin.pages.google-drive'));
        $response->assertSessionHas('gdrive_status');

        $token = GoogleDriveToken::sole();

        $this->assertSame('refresh-token-uji', Crypt::decryptString($token->refresh_token));
        $this->assertSame('drive-cps@gmail.com', $token->connected_email);
        $this->assertSame($this->admin->id, $token->connected_by);
        $this->assertNotNull($token->connected_at);

        // Token mentah tidak boleh tersimpan apa adanya di basis data.
        $this->assertNotSame('refresh-token-uji', $token->refresh_token);
        $this->assertDatabaseMissing('google_drive_tokens', ['refresh_token' => 'refresh-token-uji']);

        Http::assertSent(function ($request): bool {
            if ($request->url() !== GoogleDriveService::TOKEN_ENDPOINT) {
                return false;
            }

            $this->assertSame('authorization_code', $request['grant_type']);
            $this->assertSame('kode-otorisasi', $request['code']);
            $this->assertSame('rahasia-klien-uji', $request['client_secret']);

            return true;
        });
    }

    public function test_callback_menolak_state_yang_tidak_cocok(): void
    {
        Http::fake();

        $this->actingAs($this->admin)
            ->withSession(['google_drive_oauth_state' => 'state-asli'])
            ->get('/admin/google-drive/callback?code=kode-otorisasi&state=state-palsu')
            ->assertRedirect(route('filament.admin.pages.google-drive'))
            ->assertSessionHas('gdrive_error');

        $this->assertDatabaseCount('google_drive_tokens', 0);
        Http::assertNothingSent();
    }

    public function test_callback_menolak_permintaan_tanpa_state_di_sesi(): void
    {
        Http::fake();

        $this->actingAs($this->admin)
            ->get('/admin/google-drive/callback?code=kode-otorisasi&state=state-apa-saja')
            ->assertSessionHas('gdrive_error');

        $this->assertDatabaseCount('google_drive_tokens', 0);
    }

    public function test_callback_meneruskan_penolakan_consent_dari_google(): void
    {
        Http::fake();

        $this->actingAs($this->admin)
            ->withSession(['google_drive_oauth_state' => 'state-uji'])
            ->get('/admin/google-drive/callback?error=access_denied&state=state-uji')
            ->assertSessionHas('gdrive_error');

        $this->assertDatabaseCount('google_drive_tokens', 0);
    }

    public function test_callback_melaporkan_respons_tanpa_refresh_token(): void
    {
        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response(['access_token' => 'token-akses-uji']),
        ]);

        $this->actingAs($this->admin)
            ->withSession(['google_drive_oauth_state' => 'state-uji'])
            ->get('/admin/google-drive/callback?code=kode-otorisasi&state=state-uji')
            ->assertSessionHas('gdrive_error');

        $this->assertDatabaseCount('google_drive_tokens', 0);
    }

    public function test_menghubungkan_ulang_mengganti_koneksi_lama_dan_membuang_cache_token(): void
    {
        GoogleDriveToken::create([
            'refresh_token' => Crypt::encryptString('refresh-token-uji'),
            'connected_email' => 'lama@gmail.com',
            'connected_at' => now()->subDay(),
        ]);
        Cache::put(GoogleDriveService::ACCESS_TOKEN_CACHE_KEY, 'token-akses-uji', 3300);

        Http::fake([
            GoogleDriveService::TOKEN_ENDPOINT => Http::response([
                'access_token' => 'token-akses-uji',
                'refresh_token' => 'refresh-token-uji',
            ]),
            GoogleDriveOAuthController::USERINFO_ENDPOINT => Http::response(['email' => 'baru@gmail.com']),
        ]);

        $this->actingAs($this->admin)
            ->withSession(['google_drive_oauth_state' => 'state-uji'])
            ->get('/admin/google-drive/callback?code=kode-otorisasi&state=state-uji')
            ->assertSessionHas('gdrive_status');

        $this->assertDatabaseCount('google_drive_tokens', 1);
        $this->assertSame('refresh-token-uji', GoogleDriveToken::sole()->plainRefreshToken());
        $this->assertNull(Cache::get(GoogleDriveService::ACCESS_TOKEN_CACHE_KEY));
    }

    public function test_halaman_admin_google_drive_menampilkan_status_koneksi(): void
    {
        $this->actingAs($this->admin)
            ->get(route('filament.admin.pages.google-drive'))
            ->assertOk()
            ->assertSee('Belum terhubung')
            ->assertSee('Connect Google Drive');

        GoogleDriveToken::create([
            'refresh_token' => Crypt::encryptString('refresh-token-uji'),
            'connected_email' => 'drive-cps@gmail.com',
            'connected_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('filament.admin.pages.google-drive'))
            ->assertOk()
            ->assertSee('Terhubung')
            ->assertSee('drive-cps@gmail.com');
    }

    public function test_halaman_admin_google_drive_tertutup_untuk_employee(): void
    {
        $this->actingAs($this->employee)
            ->get(route('filament.admin.pages.google-drive'))
            ->assertForbidden();
    }
}
