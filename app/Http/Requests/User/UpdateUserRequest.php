<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Security enforced by route-level middleware (role:clinic_manager).
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = is_object($user) ? $user->id : (is_array($user) ? $user['id'] : $user);

        return [
            'role_ids' => 'sometimes|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,'.$userId,
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'cuit' => 'sometimes|nullable|string|max:20|unique:users,cuit,'.$userId,
            'specialty' => 'sometimes|nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:active,pending_activation,inactive',
        ];
    }
}
