<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'date'         => $this->date?->format('Y-m-d'),
            'total_amount' => $this->total_amount,
            'status'       => $this->status,
            'cae'          => $this->cae,
            'patient'      => new PatientResource($this->whenLoaded('patient')),
            'voucher_type' => new VoucherTypeResource($this->whenLoaded('voucherType')),
            'items'        => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments'     => InvoicePaymentResource::collection($this->whenLoaded('payments')),
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
