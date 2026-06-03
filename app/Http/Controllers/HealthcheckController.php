<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthcheckController extends Controller
{
    /**
     * Extended healthcheck. The basic /up Laravel default only confirms the
     * PHP process is alive; this endpoint also confirms the database and
     * filesystem are reachable, which is what load balancers actually need
     * to know to stop routing traffic to a broken instance.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => true,
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
        ];

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1');
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function checkStorage(): bool
    {
        try {
            // Cheap write/delete probe against the private disk we depend on.
            $disk = Storage::disk('local');
            $sentinel = 'health/'.uniqid('hc_', true).'.txt';
            $disk->put($sentinel, '1');
            $ok = $disk->exists($sentinel);
            $disk->delete($sentinel);
            return $ok;
        } catch (Throwable) {
            return false;
        }
    }
}
