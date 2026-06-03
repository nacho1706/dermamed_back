<?php

namespace Tests\Feature\Auth;

use App\Support\AuthCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesAsRole;
use Tests\TestCase;

/**
 * VerifyCsrfHeader middleware: mutations under cookie auth must carry
 * X-XSRF-TOKEN matching the XSRF-TOKEN cookie. Bearer-only clients are
 * exempt so existing API consumers keep working.
 *
 * Cookies are exercised via real /login responses (Laravel's test client
 * doesn't always forward `withCookies(...)` to the route middleware stack
 * the same way a browser does — going through /login mirrors production).
 */
class CsrfGuardTest extends TestCase
{
    use RefreshDatabase, AuthenticatesAsRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * @return array{auth: string, csrf: string}
     */
    private function loginAndExtractCookies(): array
    {
        $response = $this->postJson('/api/login', [
            'email' => 'doctor@dermamed.com',
            'password' => 'password',
        ]);
        $response->assertStatus(200);

        $cookies = $response->headers->getCookies();
        $auth = collect($cookies)->firstWhere(fn ($c) => $c->getName() === AuthCookies::AUTH_COOKIE);
        $csrf = collect($cookies)->firstWhere(fn ($c) => $c->getName() === AuthCookies::CSRF_COOKIE);

        return [
            'auth' => $auth->getValue(),
            'csrf' => $csrf->getValue(),
        ];
    }

    public function test_mutation_with_bearer_only_skips_csrf(): void
    {
        // Tests / server-to-server clients use Authorization: Bearer and
        // shouldn't need CSRF. That's how the existing test suite keeps
        // working without minting tokens.
        $this->withToken($this->tokenFor($this->doctor()))
            ->putJson('/api/me', ['name' => 'Bearer Client'])
            ->assertStatus(200);
    }

    public function test_get_request_with_bearer_skips_csrf(): void
    {
        $this->withToken($this->tokenFor($this->doctor()))
            ->getJson('/api/me')
            ->assertStatus(200);
    }

    public function test_login_emits_csrf_cookie_alongside_auth_cookie(): void
    {
        $cookies = $this->loginAndExtractCookies();
        $this->assertNotEmpty($cookies['auth']);
        $this->assertNotEmpty($cookies['csrf']);
        $this->assertNotEquals($cookies['auth'], $cookies['csrf']);
    }

    public function test_csrf_token_endpoint_is_callable_without_auth(): void
    {
        // The frontend hits /csrf-token on app boot before any mutation.
        $this->getJson('/api/csrf-token')->assertStatus(200);
    }
}
