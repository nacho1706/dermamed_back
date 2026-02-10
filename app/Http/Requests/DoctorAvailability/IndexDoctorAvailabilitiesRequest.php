<?php

namespace App\Http\Requests\DoctorAvailability;

use Illuminate\Foundation\Http\FormRequest;

class IndexDoctorAvailabilitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cantidad' => 'sometimes|integer|min:1',
            'pagina' => 'sometimes|integer|min:1',
            'doctor_id' => 'sometimes|integer|exists:users,id',
        ];
    }
}
