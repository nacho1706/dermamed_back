<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalIncomes = $this->resource->payments()
            ->whereHas('paymentMethod', function ($q) {
                $q->where('name', 'ilike', '%efectivo%');
            })
            ->sum('amount');

        $totalExpenses = (float) $this->resource->expenses()->sum('amount');

        $expectedBalance = (float) bcsub(
            bcadd((string) ($this->initial_balance ?? 0), (string) $totalIncomes, 2),
            (string) $totalExpenses,
            2
        );

        return [
            'id' => $this->id,
            'opening_time' => $this->opening_time?->toIso8601String(),
            'closing_time' => $this->closing_time?->toIso8601String(),
            'opening_balance' => $this->initial_balance,
            'closing_balance' => $this->final_balance,
            'status' => $this->status,
            'opened_by' => new UserResource($this->whenLoaded('openedBy')),
            'closed_by' => new UserResource($this->whenLoaded('closedBy')),
            'closed_by_name' => $this->closedBy?->name ?? $this->closedBy?->first_name ?? 'Sistema',
            'payments' => InvoicePaymentResource::collection($this->whenLoaded('payments')),
            'expenses' => CashExpenseResource::collection($this->whenLoaded('expenses')),
            'total_incomes' => (float) $totalIncomes,
            'total_expenses' => $totalExpenses,
            'expected_balance' => $expectedBalance,
            'justification' => $this->justification,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
