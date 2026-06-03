<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * Helpers to set and clear the auth cookie + CSRF cookie pair.
 *
 * The auth cookie carries the JWT and is HttpOnly so it cannot be exfiltrated
 * by XSS. The CSRF cookie (XSRF-TOKEN) is readable by JavaScript and is
 * echoed back in the X-XSRF-TOKEN header by axios on every mutation — the
 * server verifies double-submit equality so an attacker who can't read the
 * victim's cookie can't forge the header either.
 */
class AuthCookies
{
    public const AUTH_COOKIE = 'dermamed_token';
    public const CSRF_COOKIE = 'XSRF-TOKEN';

    /**
     * Set both cookies on the given response.
     *
     * @param  int  $ttlMinutes  Lifetime in minutes (defaults to the JWT TTL).
     */
    public static function attach(JsonResponse $response, string $token, ?int $ttlMinutes = null): JsonResponse
    {
        $ttl = $ttlMinutes ?? (int) config('jwt.ttl', 60);
        $secure = app()->environment('production');

        $response->headers->setCookie(self::makeAuthCookie($token, $ttl, $secure));
        // CSRF cookie lives 24h — it is just a double-submit nonce, not a
        // secret, and outlasting the JWT TTL prevents the "419 right after
        // the token refresh" race when axios reuses the request that was
        // queued during the refresh.
        $response->headers->setCookie(self::makeCsrfCookie(self::generateCsrfToken(), 60 * 24, $secure));

        return $response;
    }

    /**
     * Clear both cookies (logout / unauthenticated).
     */
    public static function forget(JsonResponse $response): JsonResponse
    {
        $secure = app()->environment('production');

        $response->headers->setCookie(self::makeAuthCookie('', -1, $secure));
        $response->headers->setCookie(self::makeCsrfCookie('', -1, $secure));

        return $response;
    }

    public static function generateCsrfToken(): string
    {
        return Str::random(40);
    }

    private static function makeAuthCookie(string $value, int $minutes, bool $secure): SymfonyCookie
    {
        return Cookie::make(
            name: self::AUTH_COOKIE,
            value: $value,
            minutes: $minutes,
            path: '/',
            domain: null,
            secure: $secure,
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        );
    }

    private static function makeCsrfCookie(string $value, int $minutes, bool $secure): SymfonyCookie
    {
        return Cookie::make(
            name: self::CSRF_COOKIE,
            value: $value,
            minutes: $minutes,
            path: '/',
            domain: null,
            secure: $secure,
            httpOnly: false, // readable by JS so axios can echo it in X-XSRF-TOKEN
            raw: false,
            sameSite: 'lax',
        );
    }
}
