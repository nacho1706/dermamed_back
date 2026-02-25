<?php

namespace App\Http\Requests\User;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class InviteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Capa 2: Backend - Prevención de Escalada de Privilegios
        $roleIds = $this->input('role_ids');
        if ($roleIds) {
            $systemAdminRole = Role::where('name', 'system_admin')->first();
            if ($systemAdminRole && in_array($systemAdminRole->id, (array)$roleIds)) {
                return $this->user()->isSystemAdmin();
            }
        }

        return true;
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
