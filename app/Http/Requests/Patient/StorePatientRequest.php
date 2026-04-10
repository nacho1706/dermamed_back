<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
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

            // Remove a single leading zero (local prefix like '0' + area code)
            if (str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }

            // If digits is empty after stripping, leave phone as-is for validation to reject
            if (empty($digits)) {
                return;
            }

            if (strlen($digits) === 10) {
                // Local 10-digit number (e.g., 1144445555 or 3813193828)
                $this->merge(['phone' => '+549'.$digits]);
            } elseif (strlen($digits) === 12 && str_starts_with($digits, '54')) {
                // Number with country code but missing mobile '9' (e.g., 541144445555)
                $this->merge(['phone' => '+549'.substr($digits, 2)]);
            } elseif (strlen($digits) === 13 && str_starts_with($digits, '549')) {
                // Perfect Argentinian mobile format (e.g., 5491144445555)
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
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'dni' => ['required', 'string', 'regex:/^\d{7,8}$/', \Illuminate\Validation\Rule::unique('patients', 'dni')],
            'cuit' => ['nullable', 'string', 'digits:11', \Illuminate\Validation\Rule::unique('patients', 'cuit')],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^\+[1-9]\d{10,14}$/', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'street' => ['nullable', 'string', 'max:150', 'required_with:street_number,floor,apartment'],
            'street_number' => 'nullable|string|max:10',
            'floor' => 'nullable|string|max:10',
            'apartment' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'health_insurance_id' => 'nullable|integer|exists:health_insurances,id',
            'affiliate_number' => 'nullable|string|max:100',
        ];
    }
}
