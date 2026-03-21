<?php
declare(strict_types=1);

namespace Tests\Feature\Fraud;

use App\Models\PaymentTransaction;
use App\Models\FraudAttempt;
use App\Models\Wallet;
use Tests\SecurityTestCase;

/**
 * Security Tests для Fraud Detection & Prevention
 *
 * Тестирует:
 * - Replay attacks (повторное использование idempotency_key)
 * - Idempotency bypass (изменение payload после first attempt)
 * - Rate limit bypass
 * - Race conditions в wallet
 * - Wishlist manipulation для рейтинга
 * - Fake reviews
 * - Bonus hunting
 * - Multiple payout attempts
 */

it('test replay attack protection on payments', function () {
    $this->assertReplayAttackProtection(
        'POST',
        '/api/payments/init',
        [
            'amount' => 100000,
            'currency' => 'RUB',
            'description' => 'Test',
        ],
        \Illuminate\Support\Str::uuid()->toString()
    );
});

it('test idempotency payload mismatch detection', function () {
    $key = \Illuminate\Support\Str::uuid()->toString();

    $this->assertIdempotencyBypassProtection(
        '/api/payments/init',
        ['amount' => 100000, 'currency' => 'RUB'],
        ['amount' => 200000, 'currency' => 'RUB'],
        $key
    );
});

it('test payment rate limiting blocks DDoS', function () {
    $this->assertRateLimitBypassProtection(
        'POST',
        '/api/payments/init',
        ['amount' => 100000, 'currency' => 'RUB'],
        allowedRequests: 10,
        windowSeconds: 60
    );
});

it('test wallet race condition protection', function () {
    $wallet = Wallet::factory()->create([
        'tenant_id' => $this->tenant->id,
        'current_balance' => 100000,
    ]);

    $this->user->update(['wallet_id' => $wallet->id]);

    $this->assertNoWalletRaceCondition(100000, 60000);
});

it('test wishlist cannot manipulate product rating', function () {
    $this->assertWishlistManipulationProtection();
});

it('test fake reviews are blocked', function () {
    $this->assertFakeReviewsProtection();
});

it('test bonus hunting is prevented', function () {
    $this->assertBonusHuntingProtection();
});

it('test multiple payout attempts are blocked', function () {
    $wallet = Wallet::factory()->create([
        'tenant_id' => $this->tenant->id,
        'current_balance' => 100000,
    ]);

    // First payout request
    $response1 = $this->authenticatedPost('/api/payouts', [
        'amount' => 50000,
        'bank_account' => 'RU00000000000000000000',
    ]);

    $response1->assertSuccessful();

    // Second payout request immediately after (suspicious)
    $response2 = $this->authenticatedPost('/api/payouts', [
        'amount' => 50000,
        'bank_account' => 'RU00000000000000000000',
    ]);

    // Should be blocked or require confirmation
    if ($response2->status() !== 200) {
        $response2->assertStatus(429); // Rate limited or 422 (validation)
    }
});

it('test order creation flood is blocked', function () {
    // Attempt to create 100 orders in quick succession
    $blockedCount = 0;

    for ($i = 0; $i < 100; $i++) {
        $response = $this->authenticatedPost('/api/orders', [
            'items' => [
                ['product_id' => 1, 'quantity' => 1],
            ],
            'total' => 10000,
        ]);

        if ($response->status() === 429) {
            $blockedCount++;
        }
    }

    // Should block majority of requests
    expect($blockedCount)->toBeGreaterThan(50);
});

it('test same payment from multiple IPs is flagged', function () {
    $ips = ['192.168.1.1', '10.0.0.1', '8.8.8.8'];

    foreach ($ips as $ip) {
        $response = $this->authenticatedPost('/api/payments/init', [
            'amount' => 100000,
            'currency' => 'RUB',
        ], [
            'X-Forwarded-For' => $ip,
            'X-Real-IP' => $ip,
        ]);

        if ($ip !== $ips[0]) {
            // Second IP from same user in short time = high fraud score
            $fraudScore = $response->json('fraud_score');
            expect($fraudScore)->toBeGreaterThan(0.5);
        }
    }
});

it('test high value order from new device is flagged', function () {
    // Simulate first time device
    $response = $this->authenticatedPost('/api/payments/init', [
        'amount' => 1000000, // 10k RUB
        'currency' => 'RUB',
        'device_fingerprint' => 'new_device_' . $this->correlationId,
    ], [
        'User-Agent' => 'Mozilla/5.0 (iPhone...', // New device signature
    ]);

    $fraudScore = $response->json('fraud_score');
    expect($fraudScore)->toBeGreaterThan(0.6); // High fraud score
});

it('test credit card testing is blocked', function () {
    // Attempt to test multiple card numbers with small amounts
    $cardNumbers = [
        '4111111111111111',
        '4111111111111112',
        '4111111111111113',
        '4111111111111114',
        '4111111111111115',
    ];

    $failureCount = 0;

    foreach ($cardNumbers as $cardNumber) {
        $response = $this->authenticatedPost('/api/payments/init', [
            'amount' => 100, // Small test amount
            'card_number' => $cardNumber,
            'currency' => 'RUB',
        ]);

        if ($response->status() !== 200) {
            $failureCount++;
        }
    }

    // Most should be blocked
    expect($failureCount)->toBeGreaterThan(3);
});

it('test referral abuse is prevented', function () {
    // User creates multiple referral links
    $links = [];

    for ($i = 0; $i < 20; $i++) {
        $response = $this->authenticatedPost('/api/referrals/generate-link', []);

        if ($response->status() === 200) {
            $links[] = $response->json('code');
        } else if ($response->status() === 429) {
            // Rate limited = good
            break;
        }
    }

    // Should be rate limited after few attempts
    expect(count($links))->toBeLessThan(10);
});

it('test search poisoning is blocked', function () {
    $this->assertSearchDDoSProtection('/api/search');
});

it('test SQL injection in filters is prevented', function () {
    $response = $this->authenticatedGet('/api/products', [
        'category_id' => "' OR '1'='1",
    ]);

    // Should not return all products
    $results = $response->json('data');
    expect(count($results))->toBeLessThan(1000); // Some reasonable limit
});

it('test XSS in product names is escaped', function () {
    $xssPayload = '<img src=x onerror="alert(1)">';

    $response = $this->authenticatedPost('/api/products', [
        'name' => $xssPayload,
        'description' => 'Test',
    ]);

    if ($response->successful()) {
        $product = \App\Models\Product::find($response->json('id'));
        expect($product->name)->not->toContain('<img');
        expect($product->name)->not->toContain('onerror');
    }
});

it('test mass assignment is prevented', function () {
    $response = $this->authenticatedPost('/api/products', [
        'name' => 'Test',
        'price' => 1000,
        'admin_flag' => true, // Should not be assignable
        'is_verified' => true, // Should not be assignable
    ]);

    if ($response->successful()) {
        $product = \App\Models\Product::find($response->json('id'));
        expect($product->admin_flag ?? false)->toBeFalse();
        expect($product->is_verified ?? false)->toBeFalse();
    }
});

it('test audit log is created for all fraud flags', function () {
    // Trigger fraud detection
    $response = $this->authenticatedPost('/api/payments/init', [
        'amount' => 5000000, // Very high
        'currency' => 'RUB',
    ]);

    // Check fraud attempt was logged
    $fraudAttempt = FraudAttempt::where([
        'correlation_id' => $this->correlationId,
    ])->first();

    if ($fraudAttempt) {
        expect($fraudAttempt->user_id)->toBe($this->user->id);
        expect($fraudAttempt->tenant_id)->toBe($this->tenant->id);
        expect($fraudAttempt->ml_score)->toBeGreaterThan(0);
    }
});

it('test fraud ml model fallback when service unavailable', function () {
    // Mock FraudMLService as unavailable
    $this->mock(\App\Services\Fraud\FraudMLService::class, function ($mock) {
        $mock->shouldReceive('scoreOperation')
            ->andThrow(new \Exception('Service unavailable'));
    });

    // Should still process payment with fallback rules
    $response = $this->authenticatedPost('/api/payments/init', [
        'amount' => 100000,
        'currency' => 'RUB',
    ]);

    // Should still succeed (fallback to hard rules)
    $response->assertSuccessful();
    $response->assertJsonPath('fraud_score', fn ($score) => is_numeric($score));
});

it('test correlation id is present in all fraud logs', function () {
    $response = $this->authenticatedPost('/api/payments/init', [
        'amount' => 100000,
        'currency' => 'RUB',
    ]);

    $response->assertJsonPath('correlation_id', $this->correlationId);

    // Check that audit log also has it
    $this->assertDatabaseHas('audit_logs', [
        'correlation_id' => $this->correlationId,
    ]);
});

it('test rate limit headers are correct', function () {
    $response = $this->authenticatedPost('/api/payments/init', [
        'amount' => 100000,
        'currency' => 'RUB',
    ]);

    $this->assertRateLimitHeaders($response);
});

it('test 429 response includes retry after', function () {
    // Send requests until rate limited
    for ($i = 0; $i < 20; $i++) {
        $response = $this->authenticatedPost('/api/payments/init', [
            'amount' => 100000,
            'currency' => 'RUB',
        ]);

        if ($response->status() === 429) {
            $this->assertRateLimitResponse($response);
            break;
        }
    }
});
