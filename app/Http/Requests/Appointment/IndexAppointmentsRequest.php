<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class IndexAppointmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cantidad' => 'sometimes|integer|min:1',
            'pagina' => 'sometimes|integer|min:1',
            'patient_id' => 'sometimes|integer|exists:patients,id',
            'doctor_id' => 'sometimes|integer|exists:users,id',
            'service_id' => 'sometimes|integer|exists:services,id',
            'status' => 'sometimes|required|string|in:scheduled,in_waiting_room,in_progress,completed,cancelled,no_show',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ];
    }
}
