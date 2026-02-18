<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'cuit' => 'nullable|string|digits:11|unique:patients',
            'email' => 'nullable|string|email|max:255',
            'phone' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
            'street' => 'nullable|string|max:150',
            'street_number' => 'nullable|string|max:10',
            'floor' => 'nullable|string|max:10',
            'apartment' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'insurance_provider' => 'nullable|string|max:100',
        ];
    }
}
