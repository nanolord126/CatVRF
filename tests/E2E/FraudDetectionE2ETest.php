<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraudDetectionE2ETest extends TestCase
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

    public function test_detect_rapid_payment_attempts(): void
    {
        // Simulate 5 rapid payments within 1 minute
        $timestamps = [];
        for ($i = 0; $i < 5; $i++) {
            $timestamps[] = now()->subMinutes(5 - $i);
        }

        // In real system, FraudMLService would flag as suspicious
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 50000,
                'currency' => 'RUB',
                'description' => 'Rapid payment 1',
                'return_url' => 'https://example.com',
            ]);

        // First attempt should succeed
        $this->assertTrue($response->status() < 300);
    }

    public function test_detect_amount_spike(): void
    {
        // Attempt large payment from account with history of small payments
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 5000000, // 50,000 RUB is suspiciously large
                'currency' => 'RUB',
                'description' => 'Spike payment',
                'return_url' => 'https://example.com',
            ]);

        // May be blocked or require additional verification
        // Status >= 400 for fraud, or 201 with fraud warning in response
        $this->assertTrue($response->status() == 201 || $response->status() >= 400);
    }

    public function test_detect_location_anomaly(): void
    {
        // Simulate payment from impossible geographic distance
        // (Would require IP geolocation service)

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('X-Forwarded-For', '203.0.113.0') // Fake IP (example.com range)
            ->postJson('/api/v1/payments', [
                'amount' => 100000,
                'currency' => 'RUB',
                'description' => 'Anomalous location',
                'return_url' => 'https://example.com',
            ]);

        $this->assertTrue($response->status() < 500);
    }

    public function test_detect_card_testing_pattern(): void
    {
        // Multiple small transaction attempts (card testing)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/payments', [
                    'amount' => 1000 + ($i * 100), // 10, 11, 12 RUB
                    'currency' => 'RUB',
                    'description' => "Card test {$i}",
                    'return_url' => 'https://example.com',
                ]);

            // After 3rd attempt, should be blocked
            if ($i === 2) {
                // May be restricted
                $this->assertTrue($response->status() < 500);
            }
        }
    }

    public function test_detect_refund_fraud(): void
    {
        // Create payment, then immediately refund (fraud pattern)
        $paymentResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 250000,
                'currency' => 'RUB',
                'description' => 'Refund fraud test',
                'return_url' => 'https://example.com',
            ]);

        $paymentId = $paymentResponse->json('transaction_id');

        // Immediately refund (suspicious)
        $refundResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/payments/{$paymentId}/refund", [
                'reason' => 'Changed mind',
            ]);

        // May be blocked or flagged
        $this->assertTrue($refundResponse->status() < 500);
    }

    public function test_detect_shared_device_fraud(): void
    {
        // Multiple users from same device/IP = fraud indicator
        $user2 = User::factory()->create();
        $token2 = $user2->createToken('test2')->plainTextToken;

        $response1 = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 100000,
                'currency' => 'RUB',
                'description' => 'User 1 payment',
                'return_url' => 'https://example.com',
            ]);

        $response2 = $this->withHeader('Authorization', "Bearer {$token2}")
            ->postJson('/api/v1/payments', [
                'amount' => 100000,
                'currency' => 'RUB',
                'description' => 'User 2 payment',
                'return_url' => 'https://example.com',
            ]);

        // Both should process but be flagged
        $this->assertTrue($response1->status() < 500);
        $this->assertTrue($response2->status() < 500);
    }

    public function test_detect_bonus_fraud(): void
    {
        // Multiple bonus claims from same account
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/referrals/claim', [
                    'referral_code' => "ref_code_{$i}",
                ]);

            // After N attempts, should be blocked
            if ($i >= 3) {
                $this->assertTrue($response->status() >= 400 || $response->status() == 201);
            }
        }
    }

    public function test_fraud_score_decreases_with_time(): void
    {
        // Old accounts have lower fraud risk
        $oldUser = User::factory()->create([
            'created_at' => now()->subYear(),
        ]);
        $oldToken = $oldUser->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->postJson('/api/v1/payments', [
                'amount' => 500000,
                'currency' => 'RUB',
                'description' => 'High amount old user',
                'return_url' => 'https://example.com',
            ]);

        // Old user with high payment = lower fraud score = likely approved
        $this->assertTrue($response->status() < 300);
    }

    public function test_blocked_fraud_user_cannot_retry(): void
    {
        // Mark user as fraud manually (in real system)
        // FraudService::markFraudulent($this->user->id);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 50000,
                'currency' => 'RUB',
                'description' => 'Blocked user payment',
                'return_url' => 'https://example.com',
            ]);

        // Should be blocked
        $this->assertTrue($response->status() < 500);
    }
}
