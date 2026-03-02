<?php

namespace App\Http\Requests\StockMovement;

use Illuminate\Foundation\Http\FormRequest;

class IndexStockMovementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cantidad' => 'sometimes|integer|min:1',
            'pagina' => 'sometimes|integer|min:1',
            'product_id' => 'sometimes|integer|exists:products,id',
            'type' => 'sometimes|string|in:in,out,adjustment',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ];
    }
}
