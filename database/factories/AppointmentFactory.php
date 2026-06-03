<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 30))->setTime(10, 0);

        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'service_id' => Service::query()->inRandomOrder()->value('id'),
            'scheduled_start_at' => $start,
            'scheduled_end_at' => $start->copy()->addMinutes(30),
            'status' => 'scheduled',
            'reserve_channel' => 'manual',
            'is_overbook' => false,
            'notes' => null,
        ];
    }
}
