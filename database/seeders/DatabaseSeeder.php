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
        $this->call([
            RoleSeeder::class,
            SystemAdminSeeder::class,
            PaymentMethodSeeder::class,
            VoucherTypeSeeder::class,
            ServiceSeeder::class,
            PatientSeeder::class,
            AppointmentSeeder::class,
        ]);

        // Clinic Manager and Doctor
        $director = User::firstOrCreate(
            ['email' => 'director@dermamed.com'],
            [
                'name' => 'Director Médico',
                'password' => Hash::make('password'),
                'is_active' => true,
                'status' => 'active',
            ]
        );
        // Find roles by name to be safe
        $clinicManagerRole = \App\Models\Role::where('name', 'clinic_manager')->first();
        $doctorRole = \App\Models\Role::where('name', 'doctor')->first();
        $director->roles()->syncWithoutDetaching([$clinicManagerRole->id, $doctorRole->id]);

        // Doctor
        $doctor = User::firstOrCreate(
            ['email' => 'doctor@dermamed.com'],
            [
                'name' => 'Doctor Ejemplo',
                'password' => Hash::make('password'),
                'is_active' => true,
                'status' => 'active',
                'specialty' => 'Dermatología General',
            ]
        );
        $doctor->roles()->syncWithoutDetaching([$doctorRole->id]);

        // Receptionist
        $receptionist = User::firstOrCreate(
            ['email' => 'recepcion@dermamed.com'],
            [
                'name' => 'Recepcionista',
                'password' => Hash::make('password'),
                'is_active' => true,
                'status' => 'active',
            ]
        );
        $receptionistRole = \App\Models\Role::where('name', 'receptionist')->first();
        $receptionist->roles()->syncWithoutDetaching([$receptionistRole->id]);
    }
}
