<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed lookup tables first
        $this->call([
            RoleSeeder::class,
            PaymentMethodSeeder::class,
            VoucherTypeSeeder::class,
        ]);

        // Create default admin user
        User::firstOrCreate(
            ['email' => 'admin@dermamed.com'],
            [
                'role_id' => 1, // admin
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
    }
}
