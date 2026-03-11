<?php

namespace App\Http\Requests\MedicalRecord;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id'              => 'required|integer|exists:patients,id',
            'doctor_id'               => 'required|integer|exists:users,id',
            'appointment_id'          => 'nullable|integer|exists:appointments,id',
            'date'                    => 'sometimes|date',
            'content'                 => 'required|string',
            'supplies'                => 'nullable|array',
            'supplies.*.product_id'   => 'required_with:supplies|integer|exists:products,id',
            'supplies.*.quantity'     => 'required_with:supplies|integer|min:1',
        ];
    }
}
