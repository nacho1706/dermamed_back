<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes: ->middleware('role:admin,receptionist')
     *
     * @param  string  ...$roles  Comma-separated list of allowed role names
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Load role relationship if not already loaded
        if (! $user->relationLoaded('role')) {
            $user->load('role');
        }

        // Admin always has access
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Check if user's role is in the allowed roles
        if (! in_array($user->role->name, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this resource',
            ], 403);
        }

        return $next($request);
    }
}
