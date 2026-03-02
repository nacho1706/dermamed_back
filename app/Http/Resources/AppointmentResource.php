<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'service_id' => $this->service_id,
            'scheduled_start_at' => $this->scheduled_start_at?->toIso8601String(),
            'scheduled_end_at' => $this->scheduled_end_at?->toIso8601String(),
            'status' => $this->status,
            'check_in_at' => $this->check_in_at?->toIso8601String(),
            'real_start_at' => $this->real_start_at?->toIso8601String(),
            'real_end_at' => $this->real_end_at?->toIso8601String(),
            'reserve_channel' => $this->reserve_channel,
            'is_overbook' => (bool) $this->is_overbook,
            'notes' => $this->notes,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'doctor' => new UserResource($this->whenLoaded('doctor')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'medical_record' => new MedicalRecordResource($this->whenLoaded('medicalRecord')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
