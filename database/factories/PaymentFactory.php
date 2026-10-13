<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'order_id' => Order::factory(),
            'amount' => fake()->numberBetween(500, 50000),
            'status' => PaymentStatus::Pending->value,
            'type' => 'cod',
            'session_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => PaymentStatus::Paid->value]);
    }
}
