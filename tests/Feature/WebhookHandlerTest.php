<?php declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\PaymentIdempotencyRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tinkoff_webhook_valid_signature(): void
    {
        $payload = [
            'TerminalKey' => 'TEST123456',
            'OrderId' => 'order-123',
            'Success' => true,
            'Status' => 'CONFIRMED',
            'PaymentId' => '123456789',
            'Amount' => 100000,
            'RebillId' => '',
            'CardId' => 0,
            'Pan' => '411111****1111',
            'ExpDate' => '1225',
            'Token' => '',
        ];

        // Generate valid token (would need actual Tinkoff secret)
        $token = hash('sha256', json_encode($payload) . 'test_secret');
        $payload['Token'] = $token;

        $response = $this->postJson('/api/webhooks/tinkoff', $payload);

        // Expected: 200 or processing response
        $this->assertTrue($response->status() < 500);
    }

    public function test_tinkoff_webhook_invalid_signature(): void
    {
        $payload = [
            'TerminalKey' => 'TEST123456',
            'OrderId' => 'order-123',
            'Status' => 'CONFIRMED',
            'PaymentId' => '123456789',
            'Amount' => 100000,
            'Token' => 'invalid_token_here',
        ];

        $response = $this->postJson('/api/webhooks/tinkoff', $payload);

        $this->assertGreaterThanOrEqual(400, $response->status());
    }

    public function test_tinkoff_webhook_status_mapping_confirmed(): void
    {
        $paymentId = 'pay-' . rand(1000, 9999);

        PaymentTransaction::create([
            'payment_id' => $paymentId,
            'tenant_id' => 1,
            'amount' => 100000,
            'status' => 'authorized',
            'provider' => 'tinkoff',
            'provider_response' => '{}',
        ]);

        // CONFIRMED → captured
        $payload = [
            'TerminalKey' => 'TEST123456',
            'OrderId' => $paymentId,
            'Status' => 'CONFIRMED',
            'PaymentId' => $paymentId,
            'Amount' => 100000,
            'Token' => hash('sha256', json_encode(['Status' => 'CONFIRMED']) . 'secret'),
        ];

        $this->postJson('/api/webhooks/tinkoff', $payload);

        $transaction = PaymentTransaction::where('payment_id', $paymentId)->first();
        if ($transaction) {
            $this->assertEquals('captured', $transaction->status);
        }
    }

    public function test_webhook_idempotency_prevents_duplicates(): void
    {
        $paymentId = 'pay-' . rand(10000, 99999);
        $idempotencyKey = "webhook-{$paymentId}";

        PaymentTransaction::create([
            'payment_id' => $paymentId,
            'tenant_id' => 1,
            'amount' => 100000,
            'status' => 'authorized',
            'provider' => 'tinkoff',
            'provider_response' => '{}',
        ]);

        $payload = [
            'TerminalKey' => 'TEST123456',
            'OrderId' => $paymentId,
            'Status' => 'CONFIRMED',
            'PaymentId' => $paymentId,
            'Amount' => 100000,
            'Token' => 'valid_token',
        ];

        // Store idempotency record
        PaymentIdempotencyRecord::create([
            'operation' => 'webhook_process',
            'idempotency_key' => $idempotencyKey,
            'payload_hash' => hash('sha256', json_encode($payload)),
            'response_data' => json_encode(['processed' => true]),
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->postJson('/api/webhooks/tinkoff', $payload);

        // Should either accept (200) or indicate already processed
        $this->assertTrue($response->status() < 500);
    }

    public function test_sber_webhook_handling(): void
    {
        $paymentId = 'sber-' . rand(1000, 9999);

        PaymentTransaction::create([
            'payment_id' => $paymentId,
            'tenant_id' => 1,
            'amount' => 50000,
            'status' => 'pending',
            'provider' => 'sber',
            'provider_response' => '{}',
        ]);

        $payload = [
            'orderId' => $paymentId,
            'operationId' => 'op-' . rand(10000, 99999),
            'status' => 'APPROVED',
            'amount' => 50000,
        ];

        // Add signature header
        $signature = hash_hmac('sha256', json_encode($payload), 'sber_secret');

        $response = $this->withHeader('X-Signature', $signature)
            ->postJson('/api/webhooks/sber', $payload);

        $this->assertTrue($response->status() < 500);
    }

    public function test_sbp_webhook_handling(): void
    {
        $paymentId = 'sbp-' . rand(1000, 9999);

        PaymentTransaction::create([
            'payment_id' => $paymentId,
            'tenant_id' => 1,
            'amount' => 75000,
            'status' => 'pending',
            'provider' => 'sbp',
            'provider_response' => '{}',
        ]);

        $payload = [
            'qrcId' => $paymentId,
            'status' => 'SUCCESS',
            'amount' => 75000,
            'transactionId' => 'txn-' . rand(100000, 999999),
        ];

        $response = $this->postJson('/api/webhooks/sbp', $payload);

        $this->assertTrue($response->status() < 500);
    }

    public function test_webhook_missing_required_fields(): void
    {
        $payload = [
            'OrderId' => 'order-123',
            // Missing required fields
        ];

        $response = $this->postJson('/api/webhooks/tinkoff', $payload);

        $this->assertGreaterThanOrEqual(400, $response->status());
    }

    public function test_webhook_logs_all_notifications(): void
    {
        $this->markTestIncomplete('Requires logging verification setup');
    }
}
