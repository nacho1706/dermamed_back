<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'appointment_id' => null,
            'date' => now(),
            'content' => fake()->paragraph(),
        ];
    }
}
