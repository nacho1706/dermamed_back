<?php

namespace App\Factories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserFactory
{
    public static function fromRequest($request, ?User $user = null): User
    {
        $user = $user ?? new User();
        $user->name = isset($request['name']) ? $request['name'] : $user->name;
        $user->email = isset($request['email']) ? $request['email'] : $user->email;
        
        if (isset($request['password'])) {
            $user->password = Hash::make($request['password']);
        }
        
        return $user;
    }
}
