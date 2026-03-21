<?php declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Domains\Payment\Models\PaymentTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class PaymentInitiationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_initiate_payment(): void
    {
        $user = User::factory()->create(['tenant_id' => 1]);

        $response = $this->actingAs($user)
            ->postJson('/api/payments/initiate', [
                'amount' => 10000, // копейки (100 рублей)
                'currency' => 'RUB',
                'description' => 'Оплата услуги',
                'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'payment_id',
                'status',
                'amount',
                'provider_payment_url',
                'correlation_id',
            ]);

        $this->assertDatabaseHas('payment_transactions', [
            'status' => 'pending',
            'amount' => 10000,
        ]);
    }

    public function test_payment_is_idempotent(): void
    {
        $user = User::factory()->create(['tenant_id' => 1]);
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        // First request
        $response1 = $this->actingAs($user)
            ->postJson('/api/payments/initiate', [
                'amount' => 10000,
                'currency' => 'RUB',
                'idempotency_key' => $idempotencyKey,
            ]);

        // Second request with same key
        $response2 = $this->actingAs($user)
            ->postJson('/api/payments/initiate', [
                'amount' => 10000,
                'currency' => 'RUB',
                'idempotency_key' => $idempotencyKey,
            ]);

        // Both should have same payment_id
        $this->assertEquals(
            $response1->json('payment_id'),
            $response2->json('payment_id')
        );
    }

    public function test_wallet_is_credited_after_successful_payment(): void
    {
        $user = User::factory()->create(['tenant_id' => 1]);
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'current_balance' => 0,
            'tenant_id' => 1,
        ]);

        $payment = PaymentTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 50000,
            'status' => 'authorized',
        ]);

        // Simulate webhook callback
        $this->postJson('/api/webhooks/payment-success', [
            'payment_id' => $payment->id,
            'status' => 'captured',
            'signature' => 'valid-signature',
        ]);

        $this->assertEquals(50000, $wallet->fresh()->current_balance);
    }

    public function test_fraud_check_prevents_suspicious_payment(): void
    {
        $user = User::factory()->create(['tenant_id' => 1]);

        // Simulate suspicious activity (10 requests in 1 minute)
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)
                ->postJson('/api/payments/initiate', [
                    'amount' => 100000,
                    'currency' => 'RUB',
                ]);
        }

        // 11th request should be blocked
        $response = $this->actingAs($user)
            ->postJson('/api/payments/initiate', [
                'amount' => 100000,
                'currency' => 'RUB',
            ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_payment_can_be_refunded(): void
    {
        $payment = PaymentTransaction::factory()->create([
            'status' => 'captured',
            'amount' => 50000,
        ]);

        $response = $this->actingAs($payment->user)
            ->postJson("/api/payments/{$payment->id}/refund", [
                'reason' => 'Отмена заказа',
                'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
            ]);

        $response->assertStatus(200);
        $this->assertEquals('refunded', $payment->fresh()->status);
    }
}
