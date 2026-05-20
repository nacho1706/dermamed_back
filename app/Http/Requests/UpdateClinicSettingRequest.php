<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClinicSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('clinic_manager');
    }

    public function rules(): array
    {
        return [
            'settings' => 'required|array',
            'settings.*' => 'required',
        ];
    }
}
