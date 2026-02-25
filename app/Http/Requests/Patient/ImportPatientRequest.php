<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class ImportPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Debés seleccionar un archivo CSV.',
            'file.mimes' => 'El archivo debe ser de tipo CSV (.csv).',
            'file.max' => 'El archivo no puede superar los 5 MB.',
        ];
    }
}
