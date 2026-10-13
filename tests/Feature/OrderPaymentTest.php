<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderPaymentTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function verifiedUser(): User
    {
        return User::query()->whereNotNull('email_verified_at')->firstOrFail();
    }

    private function makeOrder(User $user, string $type): array
    {
        $order = Order::create([
            'status' => 'processing',
            'total_price' => 5000,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 5000,
            'status' => 'pending',
            'type' => $type,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return [$order, $payment];
    }

    public function test_owner_sees_cod_payment_info(): void
    {
        $user = $this->verifiedUser();
        [$order] = $this->makeOrder($user, 'cod');

        $this->actingAs($user)
            ->get(route('order.pay', $order))
            ->assertOk()
            ->assertSee('Cash on Delivery')
            ->assertSee('No advance payment')
            ->assertDontSee('Submit Payment Proof');
    }

    public function test_owner_sees_bank_transfer_details_and_upload_form(): void
    {
        $user = $this->verifiedUser();
        [$order] = $this->makeOrder($user, 'bank_transfer');

        $this->actingAs($user)
            ->get(route('order.pay', $order))
            ->assertOk()
            ->assertSee('Bank Transfer')
            ->assertSee('Submit Payment Proof');
    }

    public function test_other_user_cannot_access_pay_page(): void
    {
        $owner = $this->verifiedUser();
        [$order] = $this->makeOrder($owner, 'jazzcash');

        $other = User::query()
            ->where('id', '!=', $owner->id)
            ->whereNotNull('email_verified_at')
            ->firstOrFail();

        $this->actingAs($other)
            ->get(route('order.pay', $order))
            ->assertStatus(403);
    }

    public function test_owner_can_upload_payment_proof(): void
    {
        Storage::fake('public');

        $user = $this->verifiedUser();
        [$order, $payment] = $this->makeOrder($user, 'easypaisa');

        $this->actingAs($user)
            ->post(route('order.pay.submit', $order), [
                'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 100),
            ])
            ->assertRedirect(route('order.view', $order));

        $payment->refresh();
        $this->assertNotNull($payment->payment_proof);
        Storage::disk('public')->assertExists($payment->payment_proof);
    }
}
