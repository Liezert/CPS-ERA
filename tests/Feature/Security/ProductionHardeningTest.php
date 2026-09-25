<?php

namespace Tests\Feature\Security;

use App\Filament\Resources\KnowledgeDocuments\Pages\CreateKnowledgeDocument;
use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\User;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Concerns\FakesGoogleDrive;
use Tests\TestCase;

/**
 * Audit pre-launch Stage 4: security header, konfigurasi produksi, route debug,
 * pesan error Drive, dan respons lupa kata sandi yang tidak membocorkan email terdaftar.
 */
class ProductionHardeningTest extends TestCase
{
    use FakesGoogleDrive;
    use RefreshDatabase;

    private const RESET_LINK_STATUS = 'Jika email terdaftar, tautan reset sudah dikirim.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DivisionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['division_id' => Division::where('name', 'Engineering')->value('id')]);
        $user->assignRole($role);

        return $user;
    }

    // ---- A. Security header -------------------------------------------------------------

    public function test_web_and_panel_responses_carry_security_headers(): void
    {
        foreach (['/login', '/admin/login'] as $uri) {
            $this->get($uri)
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
                ->assertHeaderMissing('Strict-Transport-Security')
                ->assertHeaderMissing('Content-Security-Policy');
        }
    }

    public function test_hsts_is_only_sent_over_https(): void
    {
        $this->get('https://localhost/login')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    // ---- B. Konfigurasi produksi ---------------------------------------------------------

    /**
     * Evaluasi ulang config/session.php dengan nilai env tertentu, lalu kembalikan env semula.
     *
     * @param  array<string, string|null>  $env
     * @return array<string, mixed>
     */
    private function sessionConfigWith(array $env): array
    {
        $backup = [];

        foreach ($env as $key => $value) {
            $backup[$key] = [$_SERVER[$key] ?? null, $_ENV[$key] ?? null, getenv($key)];

            if ($value === null) {
                unset($_SERVER[$key], $_ENV[$key]);
                putenv($key);
            } else {
                $_SERVER[$key] = $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        try {
            return require config_path('session.php');
        } finally {
            foreach ($backup as $key => [$server, $envValue, $putenv]) {
                $server === null ? $_SERVER = array_diff_key($_SERVER, [$key => 1]) : $_SERVER[$key] = $server;
                $envValue === null ? $_ENV = array_diff_key($_ENV, [$key => 1]) : $_ENV[$key] = $envValue;
                $putenv === false ? putenv($key) : putenv("{$key}={$putenv}");
            }
        }
    }

    public function test_session_cookie_is_secure_by_default_in_production(): void
    {
        $this->assertTrue($this->sessionConfigWith(['APP_ENV' => 'production', 'SESSION_SECURE_COOKIE' => null])['secure']);
        $this->assertFalse($this->sessionConfigWith(['APP_ENV' => 'local', 'SESSION_SECURE_COOKIE' => null])['secure']);
        $this->assertFalse($this->sessionConfigWith(['APP_ENV' => 'production', 'SESSION_SECURE_COOKIE' => 'false'])['secure']);
    }

    public function test_env_example_is_safe_to_copy_to_production(): void
    {
        $example = file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/^APP_DEBUG=false$/m', $example);
        // Didokumentasikan sebagai komentar: kunci yang terisi (walau kosong/null) mematikan default produksi.
        $this->assertMatchesRegularExpression('/^#\s*SESSION_SECURE_COOKIE=true$/m', $example);
        $this->assertDoesNotMatchRegularExpression('/^SESSION_SECURE_COOKIE=/m', $example);
        $this->assertMatchesRegularExpression('/^#.*SESSION_ENCRYPT=true/m', $example);
        $this->assertMatchesRegularExpression('/^SESSION_ENCRYPT=/m', $example);
    }

    // ---- C. Route debug ------------------------------------------------------------------

    public function test_debug_and_stub_routes_are_gone(): void
    {
        $admin = $this->userWithRole('admin');

        $this->get('/components-preview')->assertNotFound();
        $this->actingAs($admin)->get('/test-access')->assertNotFound();
        $this->actingAs($admin)->get('/management/learning-categories')->assertNotFound();
    }

    public function test_learning_category_menu_links_to_the_filament_resource(): void
    {
        $quality = $this->userWithRole('quality');

        $this->actingAs($quality)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('filament.admin.resources.learning-categories.index'), false);
    }

    // ---- D. Pesan error Drive ------------------------------------------------------------

    private function createKnowledgeDocumentWithPdf(): Testable
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Storage::fake('public');

        return Livewire::actingAs($this->userWithRole('quality'))
            ->test(CreateKnowledgeDocument::class)
            ->fillForm([
                'title' => 'SOP Kalibrasi',
                'division_id' => Division::where('name', 'Engineering')->value('id'),
                'type' => 'dokumen',
                'status' => 'published',
                'file_url' => UploadedFile::fake()->createWithContent('sop.pdf', "%PDF-1.4\n%%EOF"),
            ])
            ->call('create');
    }

    public function test_drive_configuration_errors_are_logged_but_not_shown_to_the_user(): void
    {
        Log::spy();
        $this->connectDriveAccount();
        config()->set('services.google_drive.folder_id_knowledge', null);

        $component = $this->createKnowledgeDocumentWithPdf()->assertHasFormErrors(['file_url']);
        $shown = $component->errors()->first('data.file_url');

        $this->assertStringNotContainsString('GOOGLE_DRIVE_FOLDER_ID_KNOWLEDGE', $shown);
        $this->assertStringNotContainsString('knowledge-documents/files', $shown);
        $this->assertSame(0, KnowledgeDocument::count());
        Log::shouldHaveReceived('error')->withArgs(
            fn (string $message, array $context = []) => str_contains(json_encode($context), 'GOOGLE_DRIVE_FOLDER_ID_KNOWLEDGE')
        );
    }

    public function test_drive_http_failure_body_is_logged_but_not_shown_to_the_user(): void
    {
        Log::spy();
        $this->fakeGoogleDriveUploadFailure();

        $component = $this->createKnowledgeDocumentWithPdf()->assertHasFormErrors(['file_url']);

        $this->assertStringNotContainsString('backendError', $component->errors()->first('data.file_url'));
        Log::shouldHaveReceived('error')->withArgs(
            fn (string $message, array $context = []) => str_contains(json_encode($context), 'backendError')
        );
    }

    // ---- E. Lupa kata sandi --------------------------------------------------------------

    public function test_forgot_password_response_is_identical_for_known_and_unknown_emails(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
        $unknown = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'tidak-terdaftar@cps.test']);

        foreach ([$known, $unknown] as $response) {
            $response->assertRedirect('/forgot-password')
                ->assertSessionHasNoErrors()
                ->assertSessionHas('status', self::RESET_LINK_STATUS);
        }
    }
}
