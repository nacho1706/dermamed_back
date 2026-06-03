<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CashShift>
 */
class CashShiftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'opening_time' => now(),
            'initial_balance' => 0,
            'user_id_opened' => User::factory(),
            'status' => 'open',
        ];
    }

    public function closed(): self
    {
        return $this->state(fn () => [
            'closing_time' => now(),
            'final_balance' => 0,
            'user_id_closed' => User::factory(),
            'status' => 'closed',
        ]);
    }
}
