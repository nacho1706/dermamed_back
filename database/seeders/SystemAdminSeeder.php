<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Capa 3: Backend - Creación del Superusuario (Seeder)
     * Este usuario solo se crea por consola.
     */
    public function run(): void
    {
        $email = env('SYSTEM_ADMIN_EMAIL', 'admin@dermamed.com');
        $password = env('SYSTEM_ADMIN_PASSWORD', 'admin1234');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'System Admin',
                'password' => Hash::make($password),
                'is_active' => true,
                'status' => 'active',
            ]
        );

        $role = Role::where('name', 'system_admin')->first();

        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}
