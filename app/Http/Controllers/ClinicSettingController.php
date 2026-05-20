<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateClinicSettingRequest;
use App\Services\ClinicSettingService;
use Illuminate\Http\JsonResponse;

class ClinicSettingController extends Controller
{
    public function __construct(private ClinicSettingService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->getPublicSettings());
    }

    public function update(UpdateClinicSettingRequest $request): JsonResponse
    {
        foreach ($request->validated('settings') as $key => $value) {
            // Whitelist: solo claves del feature.
            if (! array_key_exists($key, ClinicSettingService::DEFAULTS)) {
                continue;
            }
            $this->service->set($key, $value);
        }

        return response()->json($this->service->getPublicSettings());
    }
}
