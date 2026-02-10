<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'sometimes|required|integer|exists:patients,id',
            'doctor_id' => 'sometimes|required|integer|exists:users,id',
            'service_id' => 'sometimes|required|integer|exists:services,id',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'status' => 'sometimes|required|string|in:pending,confirmed,cancelled,attended',
            'reserve_channel' => 'sometimes|nullable|string|max:50|in:whatsapp,manual,web',
            'notes' => 'sometimes|nullable|string',
        ];
    }
}
