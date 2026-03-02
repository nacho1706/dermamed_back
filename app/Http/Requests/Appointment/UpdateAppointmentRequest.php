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
        // If scheduled_start_at or service_id changes but scheduled_end_at is not provided, recalculate scheduled_end_at
        if (($this->input('scheduled_start_at') || $this->input('service_id')) && ! $this->input('scheduled_end_at')) {
            // We need to fetch the current appointment to get missing data if needed,
            // but for simplicity, we assume if you change scheduled_start_at you might want to recalculate scheduled_end_at
            // based on the service (either new or existing).
            // Fetching the appointment here is possible via route parameter.
            $appointment = $this->route('appointment');

            $serviceId = $this->input('service_id') ?? $appointment->service_id;
            $startTimeStr = $this->input('scheduled_start_at') ?? $appointment->scheduled_start_at->format('Y-m-d H:i:s');

            $service = \App\Models\Service::find($serviceId);

            if ($service) {
                $startTime = \Carbon\Carbon::parse($startTimeStr);
                $endTime = $startTime->copy()->addMinutes($service->duration_minutes);
                $this->merge([
                    'scheduled_end_at' => $endTime->format('Y-m-d H:i:s'),
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
        // For now, let's enforce overlap check only if scheduled_start_at/scheduled_end_at are present in request.

        return [
            'patient_id' => 'sometimes|required|integer|exists:patients,id',
            'doctor_id' => 'sometimes|required|integer|exists:users,id',
            'service_id' => 'sometimes|required|integer|exists:services,id',
            'is_overbook' => 'sometimes|boolean',
            'scheduled_start_at' => [
                'sometimes',
                'required',
                'date',
                function ($attribute, $value, $fail) use ($appointmentId) {
                    // Logic to get effective doctor_id and end_time
                    $appointment = $this->route('appointment');
                    $doctorId = $this->input('doctor_id') ?? $appointment->doctor_id;
                    $endTime = $this->input('scheduled_end_at') ?? $appointment->scheduled_end_at;

                    $isOverbookValue = $this->has('is_overbook') ? $this->input('is_overbook') : $appointment->is_overbook;
                    $isOverbook = filter_var($isOverbookValue, FILTER_VALIDATE_BOOLEAN);

                    if (! $isOverbook) {
                        $rule = new \App\Rules\AppointmentOverlap($doctorId, $value, $endTime, $appointmentId);
                        $rule->validate($attribute, $value, $fail);
                    }
                },
            ],
            'scheduled_end_at' => 'sometimes|required|date|after:scheduled_start_at',
            'status' => 'sometimes|required|string|in:scheduled,in_waiting_room,in_progress,completed,cancelled,no_show',
            'reserve_channel' => 'sometimes|nullable|string|max:50|in:whatsapp,manual,web',
            'notes' => 'sometimes|nullable|string',
        ];
    }
}
