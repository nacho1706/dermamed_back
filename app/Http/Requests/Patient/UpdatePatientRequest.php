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
            'cuit' => 'sometimes|nullable|string|digits:11|unique:patients,cuit,' . $patientId,
            'email' => 'sometimes|nullable|string|email|max:255',
            'phone' => 'sometimes|nullable|string|max:50',
            'birth_date' => 'sometimes|nullable|date',
            'street' => 'sometimes|nullable|string|max:150',
            'street_number' => 'sometimes|nullable|string|max:10',
            'floor' => 'sometimes|nullable|string|max:10',
            'apartment' => 'sometimes|nullable|string|max:10',
            'city' => 'sometimes|nullable|string|max:100',
            'province' => 'sometimes|nullable|string|max:100',
            'zip_code' => 'sometimes|nullable|string|max:10',
            'country' => 'sometimes|nullable|string|max:100',
            'insurance_provider' => 'sometimes|nullable|string|max:100',
        ];
    }
}
