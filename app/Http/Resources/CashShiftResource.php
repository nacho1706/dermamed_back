<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'opening_time' => $this->opening_time?->toIso8601String(),
            'closing_time' => $this->closing_time?->toIso8601String(),
            'opening_balance' => $this->opening_balance,
            'closing_balance' => $this->closing_balance,
            'status' => $this->status,
            'opened_by' => new UserResource($this->whenLoaded('openedBy')),
            'closed_by' => new UserResource($this->whenLoaded('closedBy')),
            'payments' => InvoicePaymentResource::collection($this->whenLoaded('payments')),
            'total_payments' => $this->whenLoaded('payments', fn () => $this->payments->sum('amount')),
            'expected_balance' => $this->whenLoaded('payments', fn () => bcadd($this->opening_balance, $this->payments->sum('amount'), 2)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
