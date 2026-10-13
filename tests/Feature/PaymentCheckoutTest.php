<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStorefrontUsers;
use Tests\TestCase;

class PaymentCheckoutTest extends TestCase
{
    use RefreshDatabase;
    use CreatesStorefrontUsers;

    public function test_checkout_page_lists_active_payment_methods(): void
    {
        $user = $this->createCustomerUser();
        $product = Product::factory()->create(['published' => true, 'quantity' => 10]);
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($user)
            ->get('/checkout')
            ->assertOk()
            ->assertSee('Cash on Delivery')
            ->assertSee('JazzCash')
            ->assertSee('payment_method');
    }

    public function test_checkout_page_redirects_to_profile_without_addresses(): void
    {
        $user = $this->createVerifiedUser();
        \App\Models\Customer::create([
            'user_id' => $user->id,
            'first_name' => 'No',
            'last_name' => 'Address',
            'status' => \App\Enums\CustomerStatus::Active->value,
        ]);

        $this->actingAs($user)
            ->get('/checkout')
            ->assertRedirect(route('profile'));
    }

    public function test_place_order_with_cod_creates_order_and_payment(): void
    {
        $user = $this->createCustomerUser();
        $product = Product::factory()->create(['published' => true, 'quantity' => 10, 'price' => 1500]);
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);

        $response = $this->actingAs($user)->post('/checkout/place', ['payment_method' => 'cod']);

        $order = Order::query()->where('created_by', $user->id)->latest('id')->first();
        $this->assertNotNull($order);

        $response->assertRedirect(route('checkout.placed', $order));
        $this->assertSame(OrderStatus::Processing->value, $order->status);
        $this->assertEquals(3000, $order->total_price);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'type' => 'cod',
            'status' => PaymentStatus::Pending->value,
        ]);
        $this->assertDatabaseMissing('cart_items', ['user_id' => $user->id]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2]);

        $this->get(route('checkout.placed', $order))
            ->assertOk()
            ->assertSee('Cash on Delivery');
    }

    public function test_place_order_decrements_stock(): void
    {
        $user = $this->createCustomerUser();
        $product = Product::factory()->create(['published' => true, 'quantity' => 5, 'price' => 1000]);
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 3]);

        $this->actingAs($user)->post('/checkout/place', ['payment_method' => 'cod']);

        $this->assertSame(2, $product->fresh()->quantity);
    }

    public function test_place_order_rejects_missing_payment_method(): void
    {
        $user = $this->createCustomerUser();
        $product = Product::factory()->create(['published' => true, 'quantity' => 10]);
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($user)
            ->post('/checkout/place', [])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseMissing('orders', ['created_by' => $user->id]);
    }

    public function test_place_order_rejects_inactive_payment_method(): void
    {
        $user = $this->createCustomerUser();
        PaymentMethod::create([
            'name' => 'Hidden', 'code' => 'hidden_method', 'is_active' => false, 'sort_order' => 99,
        ]);
        $product = Product::factory()->create(['published' => true, 'quantity' => 10]);
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($user)
            ->post('/checkout/place', ['payment_method' => 'hidden_method'])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseMissing('orders', ['created_by' => $user->id]);
    }

    public function test_place_order_blocks_when_stock_insufficient(): void
    {
        $user = $this->createCustomerUser();
        $product = Product::factory()->create(['published' => true, 'quantity' => 1, 'price' => 1000]);
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 3]);

        $this->actingAs($user)
            ->from('/checkout')
            ->post('/checkout/place', ['payment_method' => 'cod'])
            ->assertRedirect('/checkout');

        $this->assertDatabaseMissing('orders', ['created_by' => $user->id]);
        $this->assertSame(1, $product->fresh()->quantity);
    }

    public function test_admin_can_manage_payment_methods(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'sanctum');

        $created = $this->postJson('/api/payment-methods', [
            'name' => 'Temp', 'code' => 'temp_method', 'description' => 'd', 'details' => 'x',
            'is_active' => true, 'sort_order' => 20,
        ])->assertSuccessful()->json('data');

        $this->putJson('/api/payment-methods/' . $created['id'], [
            'name' => 'Temp Updated', 'code' => 'temp_method', 'description' => 'd',
            'details' => 'y', 'is_active' => false, 'sort_order' => 21,
        ])->assertOk();

        $this->assertDatabaseHas('payment_methods', ['id' => $created['id'], 'name' => 'Temp Updated', 'is_active' => false]);

        $this->deleteJson('/api/payment-methods/' . $created['id'])->assertNoContent();
        $this->assertDatabaseMissing('payment_methods', ['id' => $created['id']]);
    }
}
