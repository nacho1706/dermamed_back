<?php

namespace App\Http\Middleware;

use App\Support\AuthCookies;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Double-submit CSRF check. On any mutating request that targets the cookie-
 * authenticated API, the X-XSRF-TOKEN header (sent by axios after reading
 * the XSRF-TOKEN cookie) must match the cookie value byte-for-byte.
 *
 * A cross-origin attacker cannot read the victim's XSRF-TOKEN cookie, so
 * even if a malicious page forces the browser to submit a request with the
 * auth cookie attached (in the rare case SameSite=Lax allows it), the
 * attacker cannot produce a matching header.
 */
class VerifyCsrfHeader
{
    /**
     * Routes that don't need CSRF protection. /login is the bootstrap (no
     * cookie exists yet) and /csrf-token is the helper that issues one.
     */
    private const EXEMPT = [
        'api/login',
        'api/csrf-token',
        'api/users/activate',
        'api/users/verify-token',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isExempt($request) || $this->isReadOnly($request)) {
            return $next($request);
        }

        // Only enforce when the request is authenticated by cookie. Pure
        // Bearer-token clients (legacy tests, server-to-server) skip CSRF.
        if (! $request->cookie(AuthCookies::AUTH_COOKIE)) {
            return $next($request);
        }

        $cookie = $request->cookie(AuthCookies::CSRF_COOKIE);
        $header = $request->header('X-XSRF-TOKEN');

        if (! $cookie || ! $header || ! hash_equals($cookie, $header)) {
            return response()->json([
                'success' => false,
                'message' => 'CSRF token mismatch.',
            ], 419);
        }

        return $next($request);
    }

    private function isReadOnly(Request $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    private function isExempt(Request $request): bool
    {
        foreach (self::EXEMPT as $path) {
            if ($request->is($path) || $request->is($path.'/*')) {
                return true;
            }
        }
        return false;
    }
}
