<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTreatmentReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['doctor', 'receptionist', 'clinic_manager']);
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'service_id' => 'nullable|exists:services,id',
            'doctor_id' => 'nullable|exists:users,id',
            'due_at' => 'required|date|after_or_equal:today',
            'notify_from_at' => 'nullable|date|before_or_equal:due_at',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
