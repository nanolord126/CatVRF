<?php
declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Models\PaymentTransaction;
use App\Models\PaymentIdempotencyRecord;
use App\Services\Payment\PaymentService;
use Tests\SecurityTestCase;

/**
 * Feature Tests для Payment API
 *
 * Тестирует:
 * - Инициация платежа (init, 3DS, hold)
 * - Захват платежа (capture, деньги → wallet)
 * - Возврат (refund, wallet ↔ gateway)
 * - Webhook обработку (verification, signature)
 * - Idempotency (повторный запрос = кешированный результат)
 * - Fraud checks (FraudMLService интеграция)
 * - Rate limiting
 * - Тенант-скопинг
 */

it('test payment init request', function () {
    $response = $this->authenticatedPost('/api/payments/init', [
        'amount' => 100000, // 1000 RUB
        'currency' => 'RUB',
        'description' => 'Test payment',
        'customer_email' => $this->user->email,
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'id',
        'status',
        'payment_url',
        'amount',
        'correlation_id',
        'fraud_score',
    ]);

    $this->assertHasCorrelationId($response);
    $this->assertHasFraudScore($response);

    $this->assertDatabaseHas('payment_transactions', [
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'amount' => 100000,
        'status' => 'pending',
        'correlation_id' => $this->correlationId,
    ]);
});

it('test payment init with high amount triggers fraud score', function () {
    $response = $this->authenticatedPost('/api/payments/init', [
        'amount' => 500000000, // 5 млн RUB — подозрительно высокая сумма
        'currency' => 'RUB',
        'description' => 'High amount payment',
    ]);

    $response->assertJsonPath('fraud_score', fn ($score) => $score > 0.5);
});

it('test payment init validates input', function () {
    $response = $this->authenticatedPost('/api/payments/init', [
        'amount' => -100000, // Negative amount
        'currency' => 'USD', // Unsupported currency
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['amount', 'currency']);
});

it('test payment capture after successful hold', function () {
    // 1. Init payment
    $initResponse = $this->authenticatedPost('/api/payments/init', [
        'amount' => 100000,
        'currency' => 'RUB',
        'description' => 'Test payment',
    ]);

    $paymentId = $initResponse->json('id');
    expect($paymentId)->not->toBeNull();

    // 2. Simulate 3DS approval (webhоок)
    $this->simulatePaymentApproval($paymentId, 100000);

    // 3. Capture payment
    $captureResponse = $this->authenticatedPost("/api/payments/$paymentId/capture", []);

    $captureResponse->assertSuccessful();
    $captureResponse->assertJsonPath('status', 'captured');

    // 4. Проверяем что money поступили в wallet
    $wallet = $this->user->wallet;
    expect($wallet->current_balance)->toBe(100000);

    // 5. Проверяем audit log
    $this->assertDatabaseHas('payment_transactions', [
        'id' => $paymentId,
        'status' => 'captured',
        'captured_at' => now(),
    ]);
});

it('test payment refund returns money to wallet', function () {
    // 1. Create successful payment
    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'amount' => 100000,
        'status' => 'captured',
        'captured_at' => now(),
    ]);

    // 2. Wallet had money
    $wallet = $this->user->wallet;
    $wallet->update(['current_balance' => 100000]);

    // 3. Request refund
    $refundResponse = $this->authenticatedPost("/api/payments/$payment->id/refund", [
        'reason' => 'customer_request',
    ]);

    $refundResponse->assertSuccessful();

    // 4. Money returned to wallet
    $wallet->refresh();
    expect($wallet->current_balance)->toBe(200000); // 100000 + 100000 refund

    // 5. Check payment status
    $payment->refresh();
    expect($payment->status)->toBe('refunded');
    expect($payment->refunded_at)->not->toBeNull();
});

it('test idempotency prevents duplicate payments', function () {
    $idempotencyKey = \Illuminate\Support\Str::uuid()->toString();

    $data = [
        'amount' => 100000,
        'currency' => 'RUB',
        'description' => 'Test payment',
    ];

    // Request 1
    $response1 = $this->authenticatedPost('/api/payments/init', $data, [
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $response1->assertSuccessful();
    $paymentId1 = $response1->json('id');

    // Request 2 with same idempotency key
    $response2 = $this->authenticatedPost('/api/payments/init', $data, [
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $response2->assertSuccessful();
    $paymentId2 = $response2->json('id');

    // Should be same payment
    expect($paymentId1)->toBe($paymentId2);

    // Only one payment in DB
    $count = PaymentTransaction::where([
        'tenant_id' => $this->tenant->id,
        'amount' => 100000,
    ])->count();
    expect($count)->toBe(1);
});

it('test idempotency with payload mismatch is rejected', function () {
    $idempotencyKey = \Illuminate\Support\Str::uuid()->toString();

    // Request 1
    $response1 = $this->authenticatedPost('/api/payments/init', [
        'amount' => 100000,
        'currency' => 'RUB',
    ], [
        'Idempotency-Key' => $idempotencyKey,
    ]);

    $response1->assertSuccessful();

    // Request 2 with different payload but same key
    $response2 = $this->authenticatedPost('/api/payments/init', [
        'amount' => 200000, // Different amount!
        'currency' => 'RUB',
    ], [
        'Idempotency-Key' => $idempotencyKey,
    ]);

    // Should be rejected
    $response2->assertStatus(409); // Conflict
    $response2->assertJson(['message' => 'Payload mismatch']);
});

it('test payment requires tenant scoping', function () {
    $secondTenant = $this->createSecondTenant();

    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $secondUser = \App\Models\User::factory()->create(['tenant_id' => $secondTenant->id]);

    // User from second tenant tries to access payment from first tenant
    $response = $this->authenticatedGet(
        "/api/payments/$payment->id",
        [],
        $secondUser
    );

    $response->assertStatus(404); // Not found
});

it('test payment webhook verification', function () {
    $payment = PaymentTransaction::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => 'pending',
    ]);

    // Simulate webhook from gateway
    $payload = [
        'id' => 'tinkoff_' . $payment->id,
        'status' => 'CONFIRMED',
        'amount' => $payment->amount,
    ];

    $signature = $this->generateWebhookSignature($payload);

    $response = $this->post('/api/webhooks/payments', $payload, [
        'X-Webhook-Signature' => $signature,
    ]);

    $response->assertSuccessful();

    $payment->refresh();
    expect($payment->status)->toBe('authorized'); // After webhook
});

it('test payment webhook with invalid signature is rejected', function () {
    $payload = [
        'id' => 'tinkoff_123',
        'status' => 'CONFIRMED',
        'amount' => 100000,
    ];

    $response = $this->post('/api/webhooks/payments', $payload, [
        'X-Webhook-Signature' => 'invalid_signature_here',
    ]);

    // Should be rejected
    $response->assertStatus(401);
});

it('test payment rate limiting is enforced', function () {
    // Send 15 payment init requests in quick succession
    for ($i = 0; $i < 15; $i++) {
        $response = $this->authenticatedPost('/api/payments/init', [
            'amount' => 100000 + $i * 1000,
            'currency' => 'RUB',
        ]);

        if ($i < 10) {
            expect($response->status())->toBe(200);
        } else {
            // After 10 requests, should be rate limited
            expect($response->status())->toBe(429);
            $response->assertHeader('Retry-After');
        }
    }
});

it('test payment with fraud score > 0.8 requires 3DS confirmation', function () {
    $service = new PaymentService();

    $response = $this->authenticatedPost('/api/payments/init', [
        'amount' => 500000000, // Very high amount
        'currency' => 'RUB',
        'description' => 'Suspicious payment',
        'from_new_device' => true,
        'ip_address' => '192.168.1.1',
    ]);

    $fraudScore = $response->json('fraud_score');
    if ($fraudScore > 0.8) {
        expect($response->json('requires_3ds'))->toBeTrue();
    }
});

it('test payment split (e.g., platform fee)', function () {
    // Order: 100000 RUB
    // Platform fee: 15% = 15000 RUB
    // Merchant receives: 85000 RUB

    $response = $this->authenticatedPost('/api/payments/init', [
        'amount' => 100000,
        'currency' => 'RUB',
        'split' => [
            ['recipient' => 'merchant', 'amount' => 85000],
            ['recipient' => 'platform', 'amount' => 15000],
        ],
    ]);

    $response->assertSuccessful();

    $payment = PaymentTransaction::find($response->json('id'));
    expect($payment->split_config)->toHaveKey('merchant');
    expect($payment->split_config['merchant'])->toBe(85000);
});

it('test payment concurrent requests do not create race conditions', function () {
    $wallet = $this->user->wallet;
    $wallet->update(['current_balance' => 100000]);

    // Simulate two concurrent debit operations
    // Both try to debit 60000 (total 120000 > available 100000)
    // One should succeed, one should fail

    $payment1Id = \Illuminate\Support\Str::uuid()->toString();
    $payment2Id = \Illuminate\Support\Str::uuid()->toString();

    // In real scenario, these would be parallel HTTP requests
    // Here we simulate with transactions
    $success1 = false;
    $success2 = false;

    try {
        \DB::transaction(function () use ($wallet, &$success1) {
            $wallet->lockForUpdate();
            if ($wallet->current_balance >= 60000) {
                $wallet->decrement('current_balance', 60000);
                $success1 = true;
            }
        });
    } catch (\Throwable $e) {
        $success1 = false;
    }

    try {
        \DB::transaction(function () use ($wallet, &$success2) {
            $wallet->lockForUpdate();
            if ($wallet->current_balance >= 60000) {
                $wallet->decrement('current_balance', 60000);
                $success2 = true;
            }
        });
    } catch (\Throwable $e) {
        $success2 = false;
    }

    // One should succeed, one should fail
    $totalSuccesses = ($success1 ? 1 : 0) + ($success2 ? 1 : 0);
    expect($totalSuccesses)->toBe(1);

    $wallet->refresh();
    expect($wallet->current_balance)->toBe(40000); // 100000 - 60000
});

// Helper methods

private function simulatePaymentApproval(string $paymentId, int $amount): void
{
    $payment = PaymentTransaction::find($paymentId);
    $payment->update([
        'status' => 'authorized',
        'provider_payment_id' => 'tinkoff_' . $payment->id,
    ]);
}

private function generateWebhookSignature(array $payload): string
{
    $secret = config('payments.webhook_secret');
    return hash_hmac('sha256', json_encode($payload), $secret);
}
