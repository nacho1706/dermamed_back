<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,'.$userId,
            'cuit' => 'sometimes|nullable|string|max:20|unique:users,cuit,'.$userId,
            'specialty' => 'sometimes|nullable|string|max:100',
        ];
    }
}
