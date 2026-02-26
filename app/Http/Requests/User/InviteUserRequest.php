<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class InviteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Security enforced by route-level middleware (role:clinic_manager).
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'specialty' => 'nullable|string|max:100',
        ];
    }
}
