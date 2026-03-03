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
            'per_page' => 'sometimes|integer|min:1',
            'pagina' => 'sometimes|integer|min:1',
            'name' => 'sometimes|string',
            'email' => 'sometimes|email',
            'role_id' => 'sometimes|integer|exists:roles,id',
            'role' => 'sometimes|string|exists:roles,name',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
