<?php

namespace Database\Factories;

use App\Enums\AddressType;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'type' => AddressType::Shipping->value,
            'address1' => fake()->streetAddress(),
            'address2' => '',
            'city' => fake()->city(),
            'state' => fake()->state(),
            'zipcode' => fake()->postcode(),
            'country_code' => 'geo',
            'customer_id' => Customer::factory(),
        ];
    }

    public function billing(): static
    {
        return $this->state(fn () => ['type' => AddressType::Billing->value]);
    }
}
