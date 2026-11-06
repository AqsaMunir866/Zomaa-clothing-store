<?php

namespace Tests\Feature\Auth;

use App\Enums\AddressType;
use App\Enums\CustomerStatus;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesStorefrontUsers;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesStorefrontUsers;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home'));
    }

    public function test_new_customers_can_place_an_order_without_verifying_their_email()
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'New Customer',
            'email' => 'new-customer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('home'));

        // No verification email is queued or sent; the account is usable now.
        Notification::assertNothingSent();

        $user = User::where('email', 'new-customer@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame(CustomerStatus::Active->value, $user->customer->status);

        $this->seedCountries();
        $this->createAddress($user, AddressType::Shipping->value);
        $this->createAddress($user, AddressType::Billing->value);

        $product = Product::factory()->create([
            'published' => true,
            'quantity' => 5,
            'price' => 1000,
        ]);
        CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->post('/checkout/place', ['payment_method' => 'cod'])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['created_by' => $user->id]);
    }
}
