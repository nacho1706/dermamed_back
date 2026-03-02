<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => "{$this->first_name} {$this->last_name}",
            'dni' => $this->dni,
            'cuit' => $this->cuit,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'phone' => $this->phone,
            'email' => $this->email,
            'street' => $this->street,
            'street_number' => $this->street_number,
            'floor' => $this->floor,
            'apartment' => $this->apartment,
            'city' => $this->city,
            'province' => $this->province,
            'zip_code' => $this->zip_code,
            'country' => $this->country,
            'full_address' => $this->full_address,
            'insurance_provider' => $this->whenLoaded('healthInsurance', fn () => $this->healthInsurance?->name),
            'health_insurance_id' => $this->health_insurance_id,
            'health_insurance' => $this->whenLoaded('healthInsurance'),
            'affiliate_number' => $this->affiliate_number,
            'medical_records' => MedicalRecordResource::collection($this->whenLoaded('medicalRecords')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
