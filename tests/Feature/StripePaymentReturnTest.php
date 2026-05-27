<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StripePaymentReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_success_url_uses_mod_security_safe_stripe_session_placeholder(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->onlineOrderFor($customer);
        $payment = $this->pendingOnlinePaymentFor($order);

        $this->mock(StripeCheckoutService::class, function ($mock) use ($order) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->withArgs(function (Order $checkoutOrder, string $successUrl) use ($order) {
                    return $checkoutOrder->is($order)
                        && str_contains($successUrl, 'checkout_session={CHECKOUT_SESSION_ID}')
                        && ! str_contains($successUrl, 'session_id=');
                })
                ->andReturn([
                    'id' => 'cs_test_123',
                    'url' => 'https://checkout.stripe.test/session/cs_test_123',
                ]);
        });

        $this->actingAs($customer)
            ->get(route('payments.checkout', $order))
            ->assertRedirect('https://checkout.stripe.test/session/cs_test_123');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'pending',
            'stripe_session_id' => 'cs_test_123',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
    }

    public function test_success_return_marks_matching_paid_stripe_session_as_paid(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->onlineOrderFor($customer, ['stripe_session_id' => 'cs_test_paid']);
        $payment = $this->pendingOnlinePaymentFor($order);

        $this->mock(StripeCheckoutService::class, function ($mock) use ($order) {
            $mock->shouldReceive('retrieveCheckoutSession')
                ->once()
                ->with('cs_test_paid')
                ->andReturn([
                    'id' => 'cs_test_paid',
                    'payment_status' => 'paid',
                    'payment_intent' => ['id' => 'pi_test_paid'],
                    'metadata' => ['order_id' => (string) $order->id],
                ]);
        });

        $this->actingAs($customer)
            ->get(route('storefront.orders.success', [
                'order' => $order,
                'checkout_session' => 'cs_test_paid',
            ]))
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'payment_status' => 'paid',
            'stripe_session_id' => 'cs_test_paid',
            'stripe_payment_intent' => 'pi_test_paid',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
    }

    public function test_success_return_still_accepts_legacy_session_id_parameter(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->onlineOrderFor($customer, ['stripe_session_id' => 'cs_test_legacy']);
        $payment = $this->pendingOnlinePaymentFor($order);

        $this->mock(StripeCheckoutService::class, function ($mock) use ($order) {
            $mock->shouldReceive('retrieveCheckoutSession')
                ->once()
                ->with('cs_test_legacy')
                ->andReturn([
                    'id' => 'cs_test_legacy',
                    'payment_status' => 'paid',
                    'payment_intent' => ['id' => 'pi_test_legacy'],
                    'metadata' => ['order_id' => (string) $order->id],
                ]);
        });

        $this->actingAs($customer)
            ->get(route('storefront.orders.success', [
                'order' => $order,
                'session_id' => 'cs_test_legacy',
            ]))
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'stripe_session_id' => 'cs_test_legacy',
            'stripe_payment_intent' => 'pi_test_legacy',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function onlineOrderFor(User $customer, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-'.fake()->unique()->numerify('######'),
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total_amount' => 265,
            'delivery_charge' => 100,
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'placed_at' => now(),
        ], $overrides));
    }

    private function pendingOnlinePaymentFor(Order $order): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'TXN-'.fake()->unique()->numerify('######'),
            'payment_method' => 'online',
            'amount' => $order->total_amount,
            'status' => 'pending',
        ]);
    }
}
