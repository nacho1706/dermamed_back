<?php

namespace App\Factories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserFactory
{
    public static function fromRequest($request, ?User $user = null): User
    {
        $user = $user ?? new User;
        $user->name = isset($request['name']) ? $request['name'] : $user->name;
        $user->email = isset($request['email']) ? $request['email'] : $user->email;
        $user->cuit = isset($request['cuit']) ? $request['cuit'] : $user->cuit;
        $user->specialty = isset($request['specialty']) ? $request['specialty'] : $user->specialty;
        $user->is_active = isset($request['is_active']) ? $request['is_active'] : $user->is_active;

        if (isset($request['password'])) {
            $user->password = Hash::make($request['password']);
        }

        return $user;
    }
}
