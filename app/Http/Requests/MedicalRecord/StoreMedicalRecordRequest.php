<?php

namespace App\Http\Requests\MedicalRecord;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicalRecordRequest extends FormRequest
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
            // Si se envía un appointment_id, debe ser único en la tabla:
            // no puede existir ya una evolución para ese turno.
            'appointment_id' => [
                'nullable',
                'integer',
                'exists:appointments,id',
                Rule::unique('medical_records', 'appointment_id')
                    ->whereNotNull('appointment_id'),
            ],
            'date' => 'sometimes|date',
            'content' => 'required|string',
            'supplies' => 'nullable|array',
            'supplies.*.product_id' => 'required_with:supplies|integer|exists:products,id',
            'supplies.*.quantity' => 'required_with:supplies|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_id.unique' => 'Ya existe una evolución médica registrada para este turno. No se puede crear una segunda.',
        ];
    }
}
