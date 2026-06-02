<?php

namespace App\Http\Middleware;

use App\Support\AuthCookies;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * If the request has no `Authorization` header but does carry the auth
 * cookie, lift the JWT out of the cookie and stuff it into the header so
 * the regular tymon/jwt-auth middleware (auth:api) keeps working unchanged.
 *
 * This is the single integration point between cookie-based session storage
 * and the existing JWT auth pipeline.
 */
class InjectJwtFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->headers->has('Authorization')) {
            $token = $request->cookie(AuthCookies::AUTH_COOKIE);
            if ($token) {
                $request->headers->set('Authorization', 'Bearer '.$token);
            }
        }

        return $next($request);
    }
}
