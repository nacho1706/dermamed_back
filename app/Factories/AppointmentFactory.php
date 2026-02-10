<?php

namespace App\Factories;

use App\Models\Appointment;

class AppointmentFactory
{
    public static function fromRequest($request, ?Appointment $appointment = null): Appointment
    {
        $appointment = $appointment ?? new Appointment();
        $appointment->patient_id = isset($request['patient_id']) ? $request['patient_id'] : $appointment->patient_id;
        $appointment->doctor_id = isset($request['doctor_id']) ? $request['doctor_id'] : $appointment->doctor_id;
        $appointment->service_id = isset($request['service_id']) ? $request['service_id'] : $appointment->service_id;
        $appointment->start_time = isset($request['start_time']) ? $request['start_time'] : $appointment->start_time;
        $appointment->end_time = isset($request['end_time']) ? $request['end_time'] : $appointment->end_time;
        $appointment->status = isset($request['status']) ? $request['status'] : $appointment->status;
        $appointment->reserve_channel = isset($request['reserve_channel']) ? $request['reserve_channel'] : $appointment->reserve_channel;
        $appointment->notes = isset($request['notes']) ? $request['notes'] : $appointment->notes;

        return $appointment;
    }
}
