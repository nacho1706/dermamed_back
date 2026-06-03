<?php

namespace Tests\Feature\Auth;

use App\Support\AuthCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesAsRole;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase, AuthenticatesAsRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_login_sets_both_cookies_and_returns_token(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'doctor@dermamed.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'token']);

        $cookies = $response->headers->getCookies();
        $names = collect($cookies)->map->getName()->all();
        $this->assertContains(AuthCookies::AUTH_COOKIE, $names);
        $this->assertContains(AuthCookies::CSRF_COOKIE, $names);

        // Auth cookie must be HttpOnly so XSS can't read it
        $auth = collect($cookies)->firstWhere(fn ($c) => $c->getName() === AuthCookies::AUTH_COOKIE);
        $this->assertTrue($auth->isHttpOnly());
        $this->assertSame('lax', strtolower($auth->getSameSite()));

        // CSRF cookie must NOT be HttpOnly so JS can echo it
        $csrf = collect($cookies)->firstWhere(fn ($c) => $c->getName() === AuthCookies::CSRF_COOKIE);
        $this->assertFalse($csrf->isHttpOnly());
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        $this->postJson('/api/login', [
            'email' => 'doctor@dermamed.com',
            'password' => 'definitely-wrong',
        ])->assertStatus(401);
    }

    public function test_me_with_bearer_token_works(): void
    {
        $token = $this->tokenFor($this->doctor());

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertStatus(200)
            ->assertJsonPath('data.email', 'doctor@dermamed.com');
    }

    public function test_me_without_token_returns_401(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_logout_returns_200_and_clears_cookies(): void
    {
        $token = $this->tokenFor($this->doctor());

        $response = $this->withToken($token)->postJson('/api/logout');
        $response->assertStatus(200);

        // Both auth cookies must be deleted (Set-Cookie with MaxAge=0).
        $cookies = $response->headers->getCookies();
        $authCookie = collect($cookies)->firstWhere(fn ($c) => $c->getName() === AuthCookies::AUTH_COOKIE);
        $csrfCookie = collect($cookies)->firstWhere(fn ($c) => $c->getName() === AuthCookies::CSRF_COOKIE);
        $this->assertNotNull($authCookie);
        $this->assertEmpty($authCookie->getValue());
        $this->assertNotNull($csrfCookie);
        $this->assertEmpty($csrfCookie->getValue());
    }

    public function test_refresh_rotates_the_jwt(): void
    {
        $oldToken = $this->tokenFor($this->doctor());

        $response = $this->withToken($oldToken)->postJson('/api/refresh');
        $response->assertStatus(200)->assertJsonStructure(['token']);

        $newToken = $response->json('token');
        $this->assertNotSame($oldToken, $newToken);

        $this->withToken($newToken)->getJson('/api/me')->assertStatus(200);
    }

    public function test_register_endpoint_is_no_longer_exposed(): void
    {
        // Sprint 1 closed the public register endpoint to prevent privilege
        // escalation via role_ids in the payload.
        $this->postJson('/api/register', [
            'name' => 'Hacker',
            'email' => 'hack@me.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'role_ids' => [1],
        ])->assertStatus(404);
    }

    public function test_csrf_token_endpoint_issues_cookie_without_auth(): void
    {
        $response = $this->getJson('/api/csrf-token');
        $response->assertStatus(200);

        $cookies = $response->headers->getCookies();
        $csrf = collect($cookies)->firstWhere(fn ($c) => $c->getName() === AuthCookies::CSRF_COOKIE);
        $this->assertNotNull($csrf, 'csrf-token must set XSRF-TOKEN cookie');
        $this->assertNotEmpty($csrf->getValue());
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.app', true)
            ->assertJsonPath('checks.database', true);
    }
}
