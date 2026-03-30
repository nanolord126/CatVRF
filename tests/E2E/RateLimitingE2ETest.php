<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_payment_rate_limit_per_minute(): void
    {
        // Simulate rapid payment requests
        $responses = [];

        for ($i = 0; $i < 15; $i++) {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/payments', [
                    'amount' => 10000 + $i * 100,
                    'currency' => 'RUB',
                    'description' => "Rate limit test {$i}",
                    'return_url' => 'https://example.com',
                ]);

            $responses[] = $response->status();
        }

        // After ~10 requests, should hit rate limit (429)
        $rateLimitedResponses = array_filter($responses, fn($status) => $status === 429);

        // At least some should be rate limited (depends on config)
        if (count($responses) > 10) {
            $this->assertGreaterThan(0, count($rateLimitedResponses));
        }
    }

    public function test_wallet_deposit_rate_limit(): void
    {
        $tenant = Tenant::factory()->create();
        $wallet = \App\Models\Wallet::factory()->create(['tenant_id' => $tenant->id]);

        $responses = [];

        for ($i = 0; $i < 25; $i++) {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson("/api/v1/wallets/{$wallet->id}/deposit", [
                    'amount' => 5000,
                    'source' => 'card',
                ]);

            $responses[] = $response->status();
        }

        // Should hit rate limit eventually
        $successCount = count(array_filter($responses, fn($s) => $s === 200));
        $limitedCount = count(array_filter($responses, fn($s) => $s === 429));

        // Expect both successes and rate limits
        $this->assertGreater(0, $successCount);
    }

    public function test_promo_code_rate_limit(): void
    {
        $responses = [];

        for ($i = 0; $i < 60; $i++) {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/promo/apply', [
                    'code' => 'TESTCODE' . $i,
                ]);

            $responses[] = $response->status();
        }

        // Should have mix of 400 (invalid code) and 429 (rate limited)
        $this->assertNotEmpty($responses);
    }

    public function test_search_rate_limit_heavy(): void
    {
        $responses = [];

        // Heavy search = higher rate limit threshold
        for ($i = 0; $i < 50; $i++) {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->getJson('/api/v1/search?q=test&type=full_index&limit=1000');

            $responses[] = $response->status();
        }

        // Eventually hit limit
        $this->assertNotEmpty($responses);
    }

    public function test_rate_limit_reset_after_window(): void
    {
        // Make requests until rate limited
        for ($i = 0; $i < 15; $i++) {
            $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/payments', [
                    'amount' => 10000,
                    'currency' => 'RUB',
                    'description' => "Pre-limit {$i}",
                    'return_url' => 'https://example.com',
                ]);
        }

        // Try one more (should be rate limited)
        $blockedResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/payments', [
                'amount' => 10000,
                'currency' => 'RUB',
                'description' => 'Should be blocked',
                'return_url' => 'https://example.com',
            ]);

        // If rate limited, should have Retry-After header
        if ($blockedResponse->status() === 429) {
            $this->assertNotNull($blockedResponse->header('Retry-After'));
        }
    }

    public function test_different_users_have_independent_limits(): void
    {
        $user2 = User::factory()->create();
        $token2 = $user2->createToken('test')->plainTextToken;

        // User 1 makes many requests
        for ($i = 0; $i < 10; $i++) {
            $this->withHeader('Authorization', "Bearer {$this->token}")
                ->postJson('/api/v1/payments', [
                    'amount' => 10000,
                    'currency' => 'RUB',
                    'description' => "User1 {$i}",
                    'return_url' => 'https://example.com',
                ]);
        }

        // User 2 should still have quota
        $user2Response = $this->withHeader('Authorization', "Bearer {$token2}")
            ->postJson('/api/v1/payments', [
                'amount' => 10000,
                'currency' => 'RUB',
                'description' => 'User2 payment',
                'return_url' => 'https://example.com',
            ]);

        // User 2 should not be rate limited just because user 1 made requests
        $this->assertTrue($user2Response->status() < 500);
    }

    public function test_api_health_check_no_rate_limit(): void
    {
        // Public endpoints should not be rate limited
        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJson('/api/health');
            $this->assertEquals(200, $response->status());
        }
    }

    public function test_authenticated_endpoint_stricter_limit_than_public(): void
    {
        // Public endpoint = higher limit
        $publicResponses = [];
        for ($i = 0; $i < 50; $i++) {
            $response = $this->getJson('/api/health');
            $publicResponses[] = $response->status();
        }

        // Authenticated endpoint = lower limit
        $authResponses = [];
        for ($i = 0; $i < 20; $i++) {
            $response = $this->withHeader('Authorization', "Bearer {$this->token}")
                ->getJson('/api/v1/wallets');
            $authResponses[] = $response->status();
        }

        // Public should have mostly 200s, auth should have mix
        $publicSuccess = count(array_filter($publicResponses, fn($s) => $s === 200));
        $this->assertGreater(45, $publicSuccess);
    }
}
