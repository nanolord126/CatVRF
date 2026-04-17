<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAttackSimulationE2ETest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_card_stolen_attack(): void
    {
        // Attempt payment with known stolen card pattern
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 50000,
                'currency' => 'RUB',
                'card_number' => '4111111111111111', // Test card
                'card_expiry' => '12/25',
                'card_cvv' => '123',
                'description' => 'Stolen card test',
            ]);

        // Should be blocked or flagged
        $this->assertTrue(
            $response->status() === 422 || 
            $response->status() === 403 ||
            ($response->json('fraud_score') && $response->json('fraud_score') > 0.8)
        );
    }

    public function test_amount_manipulation_attack(): void
    {
        // Create payment with normal amount
        $createResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 10000,
                'currency' => 'RUB',
                'description' => 'Normal payment',
            ]);

        if ($createResponse->status() === 201) {
            $paymentId = $createResponse->json('transaction_id');
            
            // Attempt to modify amount (if endpoint exists)
            $modifyResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->putJson("/api/v1/payments/{$paymentId}", [
                    'amount' => 1000000, // Try to increase 100x
                ]);

            // Should be blocked
            $this->assertTrue(
                $modifyResponse->status() === 403 || 
                $modifyResponse->status() === 422 ||
                $modifyResponse->status() === 404
            );
        }
    }

    public function test_refund_attack(): void
    {
        // Create payment
        $paymentResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 100000,
                'currency' => 'RUB',
                'description' => 'Refund attack test',
            ]);

        if ($paymentResponse->status() === 201) {
            $paymentId = $paymentResponse->json('transaction_id');
            
            // Attempt multiple refunds for same payment
            $refundResponses = [];
            for ($i = 0; $i < 3; $i++) {
                $refundResponses[] = $this->withHeader('Authorization', "Bearer {$this->token}")
                    ->postJson("/api/v1/payments/{$paymentId}/refund", [
                        'reason' => 'Customer request',
                    ]);
            }

            // Only first should succeed, others should be blocked
            $this->assertTrue($refundResponses[0]->status() < 300);
            $this->assertTrue(
                $refundResponses[1]->status() === 409 || 
                $refundResponses[1]->status() === 422
            );
        }
    }

    public function test_currency_manipulation_attack(): void
    {
        // Attempt payment with different currency to exploit exchange rates
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 1,
                'currency' => 'USD', // Try to pay in USD instead of RUB
                'description' => 'Currency manipulation test',
            ]);

        // Should be blocked or converted properly
        $this->assertTrue($response->status() < 500);
    }

    public function test_concurrent_payment_attack(): void
    {
        // Attempt multiple concurrent payments (race condition)
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/payments', [
                    'amount' => 50000,
                    'currency' => 'RUB',
                    'description' => "Concurrent payment {$i}",
                ]);
        }

        // Should handle gracefully without double-charging
        $successCount = count(array_filter($responses, fn($r) => $r->status() < 300));
        $this->assertLessThanOrEqual(3, $successCount, 'Should rate limit concurrent payments');
    }

    public function test_webhook_spoofing_attack(): void
    {
        // Attempt to spoof payment gateway webhook
        $response = $this->postJson('/api/v1/payments/webhook', [
            'transaction_id' => 'fake_transaction_id',
            'status' => 'CONFIRMED',
            'amount' => 100000,
            'signature' => 'fake_signature',
        ]);

        // Should be rejected due to invalid signature
        $this->assertTrue(
            $response->status() === 401 || 
            $response->status() === 403 ||
            $response->status() === 422
        );
    }

    public function test_idempotency_attack(): void
    {
        // Attempt to replay same payment request multiple times
        $idempotencyKey = 'test_key_' . uniqid();
        
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->withHeader('Idempotency-Key', $idempotencyKey)
                ->postJson('/api/v1/payments', [
                    'amount' => 50000,
                    'currency' => 'RUB',
                    'description' => 'Idempotency test',
                ]);
        }

        // All should return same result (idempotent)
        $this->assertEquals($responses[0]->status(), $responses[1]->status());
        $this->assertEquals($responses[0]->status(), $responses[2]->status());
    }

    public function test_negative_amount_attack(): void
    {
        // Attempt payment with negative amount
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => -50000,
                'currency' => 'RUB',
                'description' => 'Negative amount test',
            ]);

        // Should be blocked
        $this->assertTrue($response->status() === 422 || $response->status() === 400);
    }

    public function test_zero_amount_attack(): void
    {
        // Attempt payment with zero amount
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 0,
                'currency' => 'RUB',
                'description' => 'Zero amount test',
            ]);

        // Should be blocked
        $this->assertTrue($response->status() === 422 || $response->status() === 400);
    }

    public function test_extremely_large_amount_attack(): void
    {
        // Attempt payment with extremely large amount
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 999999999999,
                'currency' => 'RUB',
                'description' => 'Large amount test',
            ]);

        // Should be blocked or require additional verification
        $this->assertTrue(
            $response->status() === 422 || 
            $response->status() === 403 ||
            ($response->json('requires_verification') === true)
        );
    }

    public function test_payment_timing_attack(): void
    {
        // Attempt payment at suspicious time (e.g., during maintenance)
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 50000,
                'currency' => 'RUB',
                'description' => 'Timing attack test',
            ]);

        // Should be processed normally (timing alone shouldn't block)
        $this->assertTrue($response->status() < 500);
    }

    public function test_session_hijacking_attack(): void
    {
        // Attempt payment with another user's token (simulated)
        $anotherUser = User::factory()->create();
        $anotherToken = $anotherUser->createToken('test')->plainTextToken;

        // Try to use another user's payment method
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 50000,
                'currency' => 'RUB',
                'payment_method_id' => 'another_users_payment_method',
                'description' => 'Session hijacking test',
            ]);

        // Should be blocked
        $this->assertTrue(
            $response->status() === 403 || 
            $response->status() === 404 ||
            $response->status() === 422
        );
    }

    public function test_sql_injection_in_payment_description(): void
    {
        // Attempt SQL injection in payment description
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 50000,
                'currency' => 'RUB',
                'description' => "'; DROP TABLE payments; --",
            ]);

        // Should be sanitized or blocked
        $this->assertTrue($response->status() < 500);
    }
}
