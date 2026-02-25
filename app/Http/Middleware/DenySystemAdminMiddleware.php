<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DenySystemAdminMiddleware
{
    /**
     * Deny access to system_admin users for PHI-related routes.
     * 
     * This is a defense-in-depth layer. The RoleMiddleware already uses
     * a whitelist pattern that excludes system_admin, but this middleware
     * provides an explicit deny with a clear privacy-focused message.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isSystemAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied: system administrators cannot access clinical data (PHI privacy policy)',
            ], 403);
        }

        return $next($request);
    }
}
