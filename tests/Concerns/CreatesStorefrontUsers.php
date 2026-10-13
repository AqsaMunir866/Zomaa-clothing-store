<?php

namespace Tests\Concerns;

use App\Enums\AddressType;
use App\Enums\CustomerStatus;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\User;

trait CreatesStorefrontUsers
{
    protected function seedCountries(): void
    {
        if (Country::query()->count() === 0) {
            Country::insert([
                ['code' => 'geo', 'name' => 'Georgia', 'states' => null],
                ['code' => 'ind', 'name' => 'India', 'states' => null],
                ['code' => 'usa', 'name' => 'United States of America', 'states' => json_encode(['CA' => 'California'])],
                ['code' => 'ger', 'name' => 'Germany', 'states' => null],
            ]);
        }
    }

    protected function createVerifiedUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
        ], $attributes));
    }

    protected function createAdmin(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    protected function createCustomerUser(array $attributes = []): User
    {
        $this->seedCountries();

        $user = User::factory()->create(array_merge([
            'email_verified_at' => now(),
        ], $attributes));

        Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'phone' => '03001234567',
            'status' => CustomerStatus::Active->value,
        ]);

        $this->createAddress($user, AddressType::Shipping->value);
        $this->createAddress($user, AddressType::Billing->value);

        return $user->refresh();
    }

    protected function createAddress(User $user, string $type): CustomerAddress
    {
        return CustomerAddress::create([
            'type' => $type,
            'address1' => '123 Test Street',
            'address2' => '',
            'city' => 'Test City',
            'state' => 'Test State',
            'zipcode' => '12345',
            'country_code' => 'geo',
            'customer_id' => $user->id,
        ]);
    }
}
