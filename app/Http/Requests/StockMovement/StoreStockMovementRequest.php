<?php

namespace App\Http\Requests\StockMovement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type');

        // Per-type allowed reasons (conditional validation)
        $allowedReasons = match ($type) {
            'in' => ['supplier_purchase'],
            'out' => ['patient_sale', 'internal_use'],
            'adjustment' => ['inventory_correction', 'loss'],
            default => ['supplier_purchase', 'patient_sale', 'internal_use', 'inventory_correction', 'loss'],
        };

        return [
            'type' => 'required|string|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'reason' => ['required', 'string', Rule::in($allowedReasons)],
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        $type = $this->input('type');

        $reasonHint = match ($type) {
            'in' => 'Para entradas, el motivo debe ser: supplier_purchase.',
            'out' => 'Para salidas, el motivo debe ser: patient_sale o internal_use.',
            'adjustment' => 'Para ajustes, el motivo debe ser: inventory_correction o loss.',
            default => 'El motivo no es válido para el tipo de movimiento seleccionado.',
        };

        return [
            'reason.in' => $reasonHint,
        ];
    }
}
