<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Audit pre-launch: lapisan JSON API dihapus (tidak dipakai UI web) dan CSRF aktif penuh,
 * termasuk untuk POST /login dan POST /logout.
 */
class ApiLayerRemovedAndCsrfTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Middleware CSRF otomatis dilewati saat unit test; paksa aktif agar perilaku produksi yang teruji.
     */
    private function enforceCsrf(): void
    {
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    public function test_no_api_routes_are_registered(): void
    {
        $apiRoutes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->uri())
            ->filter(fn (string $uri) => str_starts_with($uri, 'api/'))
            ->values()
            ->all();

        $this->assertSame([], $apiRoutes);
    }

    public function test_login_without_csrf_token_is_rejected(): void
    {
        $this->enforceCsrf();
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertStatus(419);
        $this->assertGuest();
    }

    public function test_logout_without_csrf_token_is_rejected(): void
    {
        $this->enforceCsrf();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertStatus(419);
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_and_logout_with_csrf_token_still_work(): void
    {
        $this->enforceCsrf();
        $user = User::factory()->create();

        $this->withSession(['_token' => 'csrf-test-token'])
            ->post('/login', ['_token' => 'csrf-test-token', 'email' => $user->email, 'password' => 'password'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);

        $this->withSession(['_token' => 'csrf-test-token'])
            ->post('/logout', ['_token' => 'csrf-test-token'])
            ->assertRedirect();
        $this->assertGuest();
    }
}
