<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => null,
            'price' => fake()->randomFloat(2, 100, 10000),
            'stock' => 100,
            'min_stock' => 5,
            'brand_id' => null,
        ];
    }
}
