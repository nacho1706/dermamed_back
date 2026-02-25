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
            'start_time' => array_values(array_filter([
                'required',
                'date',
                // Block past times ONLY for standard scheduling.
                // in_progress / in_waiting_room skip this to avoid
                // latency-induced 422 errors comparing milliseconds.
                $this->input('status') === 'scheduled' || !$this->input('status')
                    ? 'after_or_equal:now'
                    : null,
                function ($attribute, $value, $fail) {
                    $status = $this->input('status');
                    if ($status !== 'in_progress' && $status !== 'in_waiting_room') {
                        $rule = new \App\Rules\AppointmentOverlap(
                            $this->input('doctor_id'),
                            $this->input('start_time'),
                            $this->input('end_time')
                        );
                        $rule->validate($attribute, $value, $fail);
                    }
                },
            ])),
            'end_time' => 'required|date|after:start_time',
            'status' => 'sometimes|required|string|in:scheduled,in_waiting_room,in_progress,completed,cancelled,no_show',
            'reserve_channel' => 'nullable|string|max:50|in:whatsapp,manual,web',
            'notes' => 'nullable|string',
        ];
    }
}
