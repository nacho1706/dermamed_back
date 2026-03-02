<?php

namespace App\Http\Requests\StockMovement;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|in:supplier_purchase,patient_sale,internal_use,adjustment',
            'notes' => 'nullable|string',
        ];
    }
}
