<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => fake()->unique()->words(3, true),
            'description' => fake()->realText(500),
            'price' => fake()->randomFloat(2, 100, 5000),
            'quantity' => fake()->numberBetween(1, 50),
            'published' => true,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
