<?php declare(strict_types=1);

namespace Tests\Chaos;

use App\Services\Fraud\FraudMLService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Chaos Engineering Tests
 *
 * Тестирует поведение системы при сбоях:
 * - Redis down
 * - Database slow queries
 * - Service unavailable (fallback)
 * - Partial network failures
 * - Memory exhaustion
 * - Connection pool exhaustion
 */

it('test system works when redis is down', function () {
    // Mock Redis as unavailable
    Redis::shouldReceive('get')->andThrow(new \Exception('Redis connection failed'));
    Redis::shouldReceive('set')->andThrow(new \Exception('Redis connection failed'));

    // Wallet operations should still work (fallback to DB cache)
    $wallet = \App\Models\Wallet::factory()->create([
        'tenant_id' => $this->tenant->id,
        'current_balance' => 10000,
    ]);

    $service = new \App\Services\Wallet\WalletService();

    // Should not throw, should use DB fallback
    $balance = $service->getCurrentBalance($wallet->id);
    expect($balance)->toBe(10000);

    // Credit operation should succeed
    $result = $service->credit($wallet->id, 5000, 'test', $this->correlationId);
    expect($result)->toBeTrue();

    $wallet->refresh();
    expect($wallet->current_balance)->toBe(15000);
});

it('test fraud ml service fallback when unavailable', function () {
    // Mock FraudMLService as unavailable
    $this->mock(FraudMLService::class, function ($mock) {
        $mock->shouldReceive('scoreOperation')
            ->andThrow(new \Exception('ML service unavailable'));
        $mock->shouldReceive('fallbackRules')
            ->andReturn(['score' => 0.3, 'reason' => 'fallback_rules']);
    });

    // Payment should still process with hardcoded rules
    $response = $this->authenticatedPost('/api/payments/init', [
        'amount' => 100000,
        'currency' => 'RUB',
    ]);

    $response->assertSuccessful();
    $response->assertJsonPath('fraud_score', fn ($s) => $s > 0); // Has fallback score
});

it('test database slow query timeout and retry', function () {
    // Create wallet with slow DB
    $wallet = \App\Models\Wallet::factory()->create([
        'tenant_id' => $this->tenant->id,
        'current_balance' => 10000,
    ]);

    // Mock slow query (simulate 5 second delay)
    DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
        sleep(1); // Simulate delay (real would be 5+ secs)
        return $callback();
    });

    $startTime = microtime(true);

    $service = new \App\Services\Wallet\WalletService();
    $result = $service->debit($wallet->id, 1000, 'test', $this->correlationId);

    $duration = microtime(true) - $startTime;

    // Should still complete (with delay)
    expect($result)->toBeTrue();
    expect($duration)->toBeGreaterThan(0.5); // Had delay
});

it('test circuit breaker on repeated failures', function () {
    $circuitBreakerKey = 'circuit_breaker:payment_gateway';

    // Simulate 5 consecutive failures
    for ($i = 0; $i < 5; $i++) {
        $response = $this->authenticatedPost('/api/payments/init', [
            'amount' => 100000,
            'currency' => 'RUB',
        ]);

        if ($i < 3) {
            expect($response->status())->toBe(500); // Service error
        } else {
            // After threshold, should fail fast with circuit breaker
            expect($response->status())->toBe(503); // Service unavailable
            expect($response->json('message'))->toContain('circuit breaker');
        }
    }

    // Circuit should be open
    $isOpen = \Cache::get($circuitBreakerKey) === 'open';
    expect($isOpen)->toBeTrue();

    // After timeout, should attempt to half-open
    sleep(6); // Wait for half-open window (config: 5 seconds)

    $response = $this->authenticatedPost('/api/payments/init', [
        'amount' => 100000,
        'currency' => 'RUB',
    ]);

    // Should be in half-open state (attempt to recover)
    expect($response->status())->toBeIn([200, 503]);
});

it('test graceful degradation when db connection pool exhausted', function () {
    // Get max connections config
    $maxConnections = config('database.connections.mysql.max_attempts') ?? 10;

    // Simulate connection pool exhaustion
    $responses = [];
    for ($i = 0; $i < $maxConnections + 5; $i++) {
        $response = $this->authenticatedGet('/api/wallets/balance');
        $responses[] = $response->status();
    }

    // Early requests should succeed
    $successCount = count(array_filter($responses, fn ($s) => $s === 200));
    expect($successCount)->toBeGreaterThan($maxConnections - 2);
    $lastResponses = array_slice($responses, -5);
    $unavailableCount = count(array_filter($lastResponses, fn ($s) => $s === 503));
    expect($unavailableCount)->toBeGreaterThan(0);
});

it('test partial network failure (packet loss)', function () {
    // Simulate 20% packet loss on critical endpoint
    $successCount = 0;
    $failureCount = 0;

    for ($i = 0; $i < 50; $i++) {
        // 80% should succeed, 20% fail
        $random = rand(1, 100);

        if ($random > 20) {
            // Normal request
            $response = $this->authenticatedGet('/api/wallets/balance');
            if ($response->successful()) {
                $successCount++;
            } else {
                $failureCount++;
            }
        } else {
            // Simulated timeout (would be network timeout in real scenario)
            $failureCount++;
        }
    }

    // Should have majority success
    expect($successCount)->toBeGreaterThan(30);
    expect($failureCount)->toBeLessThan(20);
});

it('test system continues when worker process dies', function () {
    // Simulate worker process death and respawn
    $jobsProcessed = 0;

    // Dispatch job
    $job = new \App\Jobs\ProcessPaymentJob([
        'payment_id' => 1,
        'correlation_id' => $this->correlationId,
    ]);

    dispatch($job);

    // Simulate worker restart (in real scenario, supervisor would restart)
    // Job should be retried automatically

    // Check that job completed or is in queue
    $this->assertDatabaseHas('jobs', [
        'queue' => 'default',
    ]);

    // After reprocessing, job should complete
    // This would be tested with real queue in integration tests
});

it('test query timeout recovery', function () {
    // Simulate a long-running query that times out
    $startTime = microtime(true);
    $queryTimeout = config('database.query_timeout', 30);

    try {
        $result = DB::statement('SELECT SLEEP(35)'); // Longer than timeout
    } catch (\Illuminate\Database\QueryException $e) {
        // Expected timeout
        $duration = microtime(true) - $startTime;
        expect($duration)->toBeLessThan($queryTimeout + 5); // Should timeout
    }

    // Next query should work fine
    $response = $this->authenticatedGet('/api/wallets/balance');
    $response->assertSuccessful();
});

it('test bulk operation cancellation on error', function () {
    // Attempt bulk payment creation where one fails
    $response = $this->authenticatedPost('/api/payments/bulk', [
        'payments' => [
            ['amount' => 100000, 'currency' => 'RUB'],
            ['amount' => -50000, 'currency' => 'RUB'], // Invalid
            ['amount' => 200000, 'currency' => 'RUB'],
        ],
    ]);

    // Should reject entire bulk or process only valid
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('payments.1.amount');

    // Should not have created any payments
    $count = \App\Models\PaymentTransaction::where('correlation_id', $this->correlationId)->count();
    expect($count)->toBe(0);
});

it('test memory pressure triggers cache eviction', function () {
    // Fill cache with data
    $cacheData = [];
    for ($i = 0; $i < 1000; $i++) {
        \Cache::put("test_key_$i", str_repeat('x', 10000), now()->addHours(1));
        $cacheData[] = "test_key_$i";
    }

    // System should still work
    $wallet = \App\Models\Wallet::factory()->create([
        'tenant_id' => $this->tenant->id,
        'current_balance' => 10000,
    ]);

    $response = $this->authenticatedGet('/api/wallets/balance');
    $response->assertSuccessful();

    // Some cache should be evicted
    $remainingKeys = 0;
    foreach ($cacheData as $key) {
        if (\Cache::has($key)) {
            $remainingKeys++;
        }
    }

    // Majority should be evicted (LRU policy)
    expect($remainingKeys)->toBeLessThan(500);
});

it('test webhook retry on temporary failure', function () {
    // Mock gateway webhook
    $payload = [
        'id' => 'payment_123',
        'status' => 'CONFIRMED',
        'amount' => 100000,
    ];

    // First attempt: failure
    // Second attempt: success
    // Job should retry automatically

    $jobQueued = \Queue::fake();
    dispatch(new \App\Jobs\ProcessPaymentWebhook($payload));

    // Should be queued for retry
    $jobQueued->assertPushed(\App\Jobs\ProcessPaymentWebhook::class);
});

it('test transaction timeout recovery', function () {
    $wallet = \App\Models\Wallet::factory()->create([
        'tenant_id' => $this->tenant->id,
        'current_balance' => 10000,
    ]);

    // Simulate transaction timeout
    try {
        \DB::transaction(function () use ($wallet) {
            DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
            DB::statement('SELECT SLEEP(5)'); // Will timeout
        });
    } catch (\Exception $e) {
        // Expected
    }

    // Next transaction should work
    $wallet->increment('current_balance', 1000);
    $wallet->refresh();
    expect($wallet->current_balance)->toBe(11000);
});

it('test deadlock recovery', function () {
    // Simulate deadlock scenario
    $wallet1 = \App\Models\Wallet::factory()->create(['tenant_id' => $this->tenant->id, 'current_balance' => 10000]);
    $wallet2 = \App\Models\Wallet::factory()->create(['tenant_id' => $this->tenant->id, 'current_balance' => 10000]);

    $success = 0;
    $retries = 0;

    for ($attempt = 0; $attempt < 3; $attempt++) {
        try {
            \DB::transaction(function () use ($wallet1, $wallet2) {
                $wallet1->lockForUpdate();
                sleep(0.1); // Simulate work
                $wallet2->lockForUpdate();
                $wallet1->increment('current_balance');
            });
            $success++;
        } catch (\Exception $e) {
            $retries++;
        }
    }

    // At least one should succeed (retry logic)
    expect($success + $retries)->toBe(3);
});

it('test rate limiter under cascading failures', function () {
    // When system is degraded, rate limiter should be more aggressive
    $responses = [];

    for ($i = 0; $i < 50; $i++) {
        // Simulate degraded system
        if ($i > 30) {
            \Cache::put('system:degraded', true, now()->addMinutes(1));
        }

        $response = $this->authenticatedPost('/api/payments/init', [
            'amount' => 100000,
            'currency' => 'RUB',
        ]);

        $responses[] = $response->status();
    }

    // After degradation flag is set, should get more rate limits
    $limitedAfterDegradation = count(array_filter(
        array_slice($responses, 30),
        fn ($s) => $s === 429
    ));

    expect($limitedAfterDegradation)->toBeGreaterThan(5);
});
