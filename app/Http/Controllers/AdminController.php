<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    /**
     * System Admin technical dashboard endpoint.
     * Returns basic system health information.
     */
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'laravel_version' => app()->version(),
            'php_version' => phpversion(),
        ]);
    }
}
