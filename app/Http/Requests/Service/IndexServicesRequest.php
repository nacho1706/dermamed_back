<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class IndexServicesRequest extends FormRequest
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
        ];
    }
}
