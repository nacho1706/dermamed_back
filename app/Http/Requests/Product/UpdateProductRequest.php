<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:150',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|nullable|numeric|min:0',
            'min_stock' => 'sometimes|integer|min:0',
            'brand_id' => 'nullable|integer|exists:brands,id',
        ];
    }
}
