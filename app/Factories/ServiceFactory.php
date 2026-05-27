<?php

namespace App\Factories;

use App\Models\Service;

class ServiceFactory
{
    public static function fromRequest($request, ?Service $service = null): Service
    {
        $service = $service ?? new Service;
        $service->name = isset($request['name']) ? $request['name'] : $service->name;
        $service->description = isset($request['description']) ? $request['description'] : $service->description;
        $service->price = isset($request['price']) ? $request['price'] : $service->price;
        $service->duration_minutes = isset($request['duration_minutes']) ? $request['duration_minutes'] : $service->duration_minutes;

        if (array_key_exists('followup_default_days', $request)) {
            $service->followup_default_days = $request['followup_default_days'];
        }
        if (array_key_exists('followup_notify_anticipation_days', $request)) {
            $service->followup_notify_anticipation_days = $request['followup_notify_anticipation_days'];
        }

        return $service;
    }
}
