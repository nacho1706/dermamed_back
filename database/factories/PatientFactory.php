<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a unique 7 or 8-digit DNI string
        $length = fake()->randomElement([7, 8]);
        $dni = (string) fake()->unique()->numerify(str_repeat('#', $length));

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'dni' => $dni,
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'birth_date' => fake()->optional()->date(),
        ];
    }
}
