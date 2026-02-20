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

        // Load roles relationship if not already loaded
        if (! $user->relationLoaded('roles')) {
            $user->load('roles');
        }

        // Check if user has ANY of the allowed roles
        $hasRole = $user->roles->contains(function ($role) use ($roles) {
            return in_array($role->name, $roles);
        });

        if (! $hasRole) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this resource',
            ], 403);
        }

        return $next($request);
    }
}
