<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'duration_minutes' => $this->duration_minutes,
            'doctor_commission_percentage' => $this->doctor_commission_percentage,
            'followup_default_days' => $this->followup_default_days,
            'followup_notify_anticipation_days' => $this->followup_notify_anticipation_days,
        ];
    }
}
