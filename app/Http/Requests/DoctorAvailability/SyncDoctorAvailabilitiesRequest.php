<?php

namespace App\Http\Requests\DoctorAvailability;

use Illuminate\Foundation\Http\FormRequest;

class SyncDoctorAvailabilitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id'                       => 'required|integer|exists:users,id',
            'availabilities'                  => 'required|array',
            'availabilities.*.day_of_week'    => 'required|integer|between:0,6',
            'availabilities.*.start_time'     => 'required|date_format:H:i',
            'availabilities.*.end_time'       => 'required|date_format:H:i',
        ];
    }
}
