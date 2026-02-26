<?php

namespace App\Factories;

use App\Models\DoctorAvailability;

class DoctorAvailabilityFactory
{
    public static function fromRequest($request, ?DoctorAvailability $availability = null): DoctorAvailability
    {
        $availability = $availability ?? new DoctorAvailability;
        $availability->doctor_id = isset($request['doctor_id']) ? $request['doctor_id'] : $availability->doctor_id;
        $availability->day_of_week = isset($request['day_of_week']) ? $request['day_of_week'] : $availability->day_of_week;
        $availability->start_time = isset($request['start_time']) ? $request['start_time'] : $availability->start_time;
        $availability->end_time = isset($request['end_time']) ? $request['end_time'] : $availability->end_time;

        return $availability;
    }
}
