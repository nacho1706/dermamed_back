<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // If start_time or service_id changes but end_time is not provided, recalculate end_time
        if (($this->input('start_time') || $this->input('service_id')) && !$this->input('end_time')) {
             // We need to fetch the current appointment to get missing data if needed, 
             // but for simplicity, we assume if you change start_time you might want to recalculate end_time 
             // based on the service (either new or existing). 
             // Fetching the appointment here is possible via route parameter.
             $appointment = $this->route('appointment');
             
             $serviceId = $this->input('service_id') ?? $appointment->service_id;
             $startTimeStr = $this->input('start_time') ?? $appointment->start_time->format('Y-m-d H:i:s');
             
             $service = \App\Models\Service::find($serviceId);
             
             if ($service) {
                $startTime = \Carbon\Carbon::parse($startTimeStr);
                $endTime = $startTime->copy()->addMinutes($service->duration_minutes);
                $this->merge([
                    'end_time' => $endTime->format('Y-m-d H:i:s'),
                ]);
             }
        }
    }

    public function rules(): array
    {
        $appointmentId = $this->route('appointment') ? $this->route('appointment')->id : null;
        // Use inputs or fall back to current appointment data for validation if not present in request?
        // Actually, validation rules run on input data. If input is missing, 'sometimes' rule handles it.
        // But for Overlap checking, we need the effective start/end time.
        // It's tricky to validate overlap on partial updates without merging the full state.
        // For now, let's enforce overlap check only if start_time/end_time are present in request.
        
        return [
            'patient_id' => 'sometimes|required|integer|exists:patients,id',
            'doctor_id' => 'sometimes|required|integer|exists:users,id',
            'service_id' => 'sometimes|required|integer|exists:services,id',
            'start_time' => [
                'sometimes',
                'required',
                'date',
                 function ($attribute, $value, $fail) use ($appointmentId) {
                    // Logic to get effective doctor_id and end_time
                    $appointment = $this->route('appointment');
                    $doctorId = $this->input('doctor_id') ?? $appointment->doctor_id;
                    $endTime = $this->input('end_time') ?? $appointment->end_time;
                    
                    $rule = new \App\Rules\AppointmentOverlap($doctorId, $value, $endTime, $appointmentId);
                    $rule->validate($attribute, $value, $fail);
                 }
            ],
            'end_time' => 'sometimes|required|date|after:start_time',
            'status' => 'sometimes|required|string|in:scheduled,in_waiting_room,in_progress,completed,cancelled,no_show,pending,confirmed',
            'reserve_channel' => 'sometimes|nullable|string|max:50|in:whatsapp,manual,web',
            'notes' => 'sometimes|nullable|string',
        ];
    }
}
