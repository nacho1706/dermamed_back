<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
            // Lift the JWT out of the auth cookie into the Authorization
            // header before jwt-auth runs, so cookie sessions and legacy
            // Bearer tokens both work.
            \App\Http\Middleware\InjectJwtFromCookie::class,
        ]);
        $middleware->api(append: [
            // Enforce double-submit CSRF on mutating cookie-auth requests.
            \App\Http\Middleware\VerifyCsrfHeader::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Reminders go out once a day in the evening (was hourly — that was
        // re-processing every patient 24x per day for no reason).
        $schedule->command('appointments:send-reminders')->dailyAt('18:00');

        // Marks no-show appointments where the patient never checked in.
        // Runs every 15 minutes so the dashboard reflects reality without
        // hammering the DB.
        $schedule->command('app:mark-no-show-appointments')->everyFifteenMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 404 - Model not found
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $previous = $e->getPrevious();
                $model = $previous instanceof ModelNotFoundException
                    ? class_basename($previous->getModel())
                    : 'Resource';

                return response()->json([
                    'success' => false,
                    'message' => "{$model} not found",
                ], 404);
            }
        });

        // 401 - Unauthenticated
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }
        });

        // 401 - JWT auth middleware tries to redirect to the "login" named
        // route when the token is missing; this is an API-only app so that
        // route does not exist. Map the resulting RouteNotFoundException to
        // 401 instead of letting it surface as 500.
        $exceptions->render(function (RouteNotFoundException $e, Request $request) {
            if (($request->is('api/*') || $request->expectsJson()) && str_contains($e->getMessage(), '[login]')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }
        });

        // 403 - Forbidden
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Forbidden',
                ], 403);
            }
        });

        // 403 - Policy / Gate denied
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'This action is unauthorized.',
                ], 403);
            }
        });

        // 409 - PostgreSQL Unique Violation (23505)
        $exceptions->render(function (\Illuminate\Database\UniqueConstraintViolationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de unicidad: Ya existe un registro con esos datos.',
                    'code' => '23505'
                ], 409);
            }
        });

        // Fallback para QueryException 23505
        $exceptions->render(function (\Illuminate\Database\QueryException $e, Request $request) {
            if (($request->is('api/*') || $request->expectsJson()) && $e->getCode() === '23505') {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de unicidad: Ya existe un registro con esos datos.',
                    'code' => '23505'
                ], 409);
            }
        });
    })->create();
