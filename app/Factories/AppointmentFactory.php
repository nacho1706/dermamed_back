<?php

namespace App\Factories;

use App\Models\Appointment;

class AppointmentFactory
{
    public static function fromRequest($request, ?Appointment $appointment = null): Appointment
    {
        $appointment = $appointment ?? new Appointment;
        $appointment->patient_id = isset($request['patient_id']) ? $request['patient_id'] : $appointment->patient_id;
        $appointment->doctor_id = isset($request['doctor_id']) ? $request['doctor_id'] : $appointment->doctor_id;
        $appointment->service_id = isset($request['service_id']) ? $request['service_id'] : $appointment->service_id;
        $appointment->scheduled_start_at = isset($request['scheduled_start_at']) ? $request['scheduled_start_at'] : $appointment->scheduled_start_at;
        $appointment->scheduled_end_at = isset($request['scheduled_end_at']) ? $request['scheduled_end_at'] : $appointment->scheduled_end_at;
        $appointment->status = isset($request['status']) ? $request['status'] : $appointment->status;
        $appointment->reserve_channel = isset($request['reserve_channel']) ? $request['reserve_channel'] : $appointment->reserve_channel;
        $appointment->notes = isset($request['notes']) ? $request['notes'] : $appointment->notes;

        return $appointment;
    }
}
