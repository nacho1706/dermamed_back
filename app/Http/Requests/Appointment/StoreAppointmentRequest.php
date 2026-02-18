<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if (!$this->input('end_time') && $this->input('start_time') && $this->input('service_id')) {
            $service = \App\Models\Service::find($this->input('service_id'));
            if ($service) {
                $startTime = \Carbon\Carbon::parse($this->input('start_time'));
                $endTime = $startTime->copy()->addMinutes($service->duration_minutes);
                $this->merge([
                    'end_time' => $endTime->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|integer|exists:patients,id',
            'doctor_id' => 'required|integer|exists:users,id',
            'service_id' => 'required|integer|exists:services,id',
            'start_time' => [
                'required',
                'date',
                new \App\Rules\AppointmentOverlap(
                    $this->input('doctor_id'),
                    $this->input('start_time'),
                    $this->input('end_time')
                ),
            ],
            'end_time' => 'required|date|after:start_time',
            'status' => 'sometimes|string|in:pending,confirmed,cancelled,attended',
            'reserve_channel' => 'nullable|string|max:50|in:whatsapp,manual,web',
            'notes' => 'nullable|string',
        ];
    }
}

