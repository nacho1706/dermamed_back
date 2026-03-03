<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoicePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'payment_date' => $this->payment_date?->format('Y-m-d'),
            'cash_shift_id' => $this->cash_shift_id,
            'payment_method' => new PaymentMethodResource($this->whenLoaded('paymentMethod')),
            'invoice' => $this->whenLoaded('invoice', function() {
                return [
                    'id' => $this->invoice->id,
                    'total' => $this->invoice->total_amount,
                ];
            }),
        ];
    }
}
