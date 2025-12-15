<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class IndexUsersRequest extends FormRequest
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
            'email' => 'sometimes|email',
        ];
    }
}
