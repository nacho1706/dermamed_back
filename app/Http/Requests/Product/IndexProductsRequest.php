<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class IndexProductsRequest extends FormRequest
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
            'name' => 'sometimes|string',
            'brand_id' => 'sometimes|integer|exists:brands,id',
            'sort' => 'sometimes|string|in:price_asc,price_desc,name_asc,name_desc,stock_asc,stock_desc',
            'stock_status' => 'sometimes|string|in:low',
            'trashed' => 'sometimes|string|in:true,false',
        ];
    }
}
