<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|integer|exists:patients,id',
            'doctor_id' => 'required|integer|exists:users,id',
            'service_id' => 'required|integer|exists:services,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'status' => 'sometimes|string|in:pending,confirmed,cancelled,attended',
            'reserve_channel' => 'nullable|string|max:50|in:whatsapp,manual,web',
            'notes' => 'nullable|string',
        ];
    }
}
