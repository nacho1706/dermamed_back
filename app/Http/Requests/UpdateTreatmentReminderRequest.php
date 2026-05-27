<?php

namespace App\Http\Requests;

use App\Models\TreatmentReminder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTreatmentReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['doctor', 'receptionist', 'clinic_manager']);
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(TreatmentReminder::STATUSES)],
            'contacted_via' => ['sometimes', Rule::in(TreatmentReminder::CHANNELS)],
            'dismissed_reason' => 'sometimes|nullable|string|max:2000',
            'due_at' => 'sometimes|date',
            'notify_from_at' => 'sometimes|date|before_or_equal:due_at',
            'notes' => 'sometimes|nullable|string|max:2000',
            // Para POST /postpone
            'postpone_days' => 'sometimes|integer|min:1|max:365',
        ];
    }
}
