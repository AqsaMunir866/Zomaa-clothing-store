<?php

namespace Database\Factories;

use App\Enums\CustomerStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->numerify('03#########'),
            'status' => CustomerStatus::Active->value,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => CustomerStatus::Disabled->value]);
    }
}
