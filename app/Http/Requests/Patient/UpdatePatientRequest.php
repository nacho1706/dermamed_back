<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $patientId = $this->route('patient');

        return [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'cuit' => 'sometimes|nullable|string|max:20|unique:patients,cuit,' . $patientId,
            'email' => 'sometimes|nullable|string|email|max:255',
            'phone' => 'sometimes|nullable|string|max:50',
            'birth_date' => 'sometimes|nullable|date',
            'address' => 'sometimes|nullable|string|max:255',
            'insurance_provider' => 'sometimes|nullable|string|max:100',
        ];
    }
}
