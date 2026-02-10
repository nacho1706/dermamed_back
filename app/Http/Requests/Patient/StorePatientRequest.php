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
            'cuit' => 'nullable|string|max:20|unique:patients',
            'email' => 'nullable|string|email|max:255',
            'phone' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'insurance_provider' => 'nullable|string|max:100',
        ];
    }
}
