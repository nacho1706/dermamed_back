<?php

namespace App\Http\Requests\MedicalRecord;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalRecordRequest extends FormRequest
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
            'appointment_id' => 'sometimes|nullable|integer|exists:appointments,id',
            'date' => 'sometimes|date',
            'content' => 'sometimes|required|string',
        ];
    }
}
