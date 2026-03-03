<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\PatientResource;
use App\Http\Resources\VoucherTypeResource;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\InvoiceItemResource;
use App\Http\Resources\InvoicePaymentResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->format('Y-m-d'),
            'total' => (float) $this->total_amount, // Mapped for frontend compatibility
            'total_amount' => $this->total_amount, // Kept for backwards compatibility
            'status' => $this->status,
            'cae' => $this->cae,
            'appointment_id' => $this->appointment_id,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'voucher_type' => new VoucherTypeResource($this->whenLoaded('voucherType')),
            'appointment' => new AppointmentResource($this->whenLoaded('appointment')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => InvoicePaymentResource::collection($this->payments),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
