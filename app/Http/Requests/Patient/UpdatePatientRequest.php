<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone') && ! empty($this->input('phone'))) {
            $phone = (string) $this->input('phone');
            $digits = preg_replace('/[^0-9]/', '', $phone);

            if (str_starts_with($digits, '0')) {
                $digits = ltrim($digits, '0');
            }

            if (strlen($digits) === 10) {
                // Local 10-digit number (e.g., 1144445555 or 3813193828)
                $this->merge(['phone' => '+549'.$digits]);
            } elseif (strlen($digits) === 12 && str_starts_with($digits, '54')) {
                // Number with country code but missing mobile '9' (e.g., 541144445555)
                $this->merge(['phone' => '+549'.substr($digits, 2)]);
            } elseif (strlen($digits) === 13 && str_starts_with($digits, '549')) {
                // Perfect Argentinian mobile format
                $this->merge(['phone' => '+'.$digits]);
            } elseif (! empty($digits)) {
                // Keep the + if it had one (for other countries)
                $hasPlus = str_starts_with(trim($phone), '+');
                $this->merge(['phone' => ($hasPlus ? '+' : '').$digits]);
            }
        }
    }

    public function rules(): array
    {
        $patient = $this->route('patient');
        $patientId = $patient ? $patient->id : null;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'dni' => ['sometimes', 'required', 'string', 'regex:/^\d{7,8}$/', \Illuminate\Validation\Rule::unique('patients', 'dni')->ignore($patientId)],
            'cuit' => ['sometimes', 'nullable', 'string', 'digits:11', \Illuminate\Validation\Rule::unique('patients', 'cuit')->ignore($patientId)],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255', \Illuminate\Validation\Rule::unique('patients')->ignore($patientId)],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^\+[1-9]\d{10,14}$/', 'max:20'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'street' => ['sometimes', 'nullable', 'string', 'max:150', 'required_with:street_number,floor,apartment'],
            'street_number' => ['sometimes', 'nullable', 'string', 'max:10'],
            'floor' => ['sometimes', 'nullable', 'string', 'max:10'],
            'apartment' => ['sometimes', 'nullable', 'string', 'max:10'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'province' => ['sometimes', 'nullable', 'string', 'max:100'],
            'zip_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'health_insurance_id' => ['sometimes', 'nullable', 'integer', 'exists:health_insurances,id'],
            'affiliate_number' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
