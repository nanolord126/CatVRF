<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Payment;

use App\Services\Payment\IdempotencyService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * IdempotencyService Unit Tests
 *
 * Tests atomic idempotency with Redis Lua scripts.
 */
final class IdempotencyServiceTest extends TestCase
{
    use RefreshDatabase;

    private IdempotencyService $idempotencyService;
    private RedisFactory $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = app(RedisFactory::class);
        $cache = app(CacheRepository::class);
        $logger = app(\Psr\Log\LoggerInterface::class);

        $this->idempotencyService = new IdempotencyService(
            $this->redis,
            $cache,
            $logger,
        );

        // Clear Redis before each test
        Redis::connection()->flushdb();
    }

    protected function tearDown(): void
    {
        Redis::connection()->flushdb();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_null_for_new_operation(): void
    {
        $result = $this->idempotencyService->check(
            operation: 'payment_init',
            idempotencyKey: 'test-key-123',
            payload: ['amount' => 10000],
        );

        $this->assertNull($result);
    }

    #[Test]
    public function it_stores_response_for_new_operation(): void
    {
        $idempotencyKey = 'test-key-456';
        $payload = ['amount' => 10000];
        $response = ['payment_id' => 'pay_123'];

        // First check should return null
        $firstCheck = $this->idempotencyService->check(
            operation: 'payment_init',
            idempotencyKey: $idempotencyKey,
            payload: $payload,
        );
        $this->assertNull($firstCheck);

        // Store response
        $this->idempotencyService->storeResponse(
            operation: 'payment_init',
            idempotencyKey: $idempotencyKey,
            response: $response,
        );

        // Second check with same payload should return stored response
        $secondCheck = $this->idempotencyService->check(
            operation: 'payment_init',
            idempotencyKey: $idempotencyKey,
            payload: $payload,
        );
        $this->assertEquals($response, $secondCheck);
    }

    #[Test]
    public function it_throws_exception_on_payload_mismatch(): void
    {
        $idempotencyKey = 'test-key-789';

        // First check with payload
        $this->idempotencyService->check(
            operation: 'payment_init',
            idempotencyKey: $idempotencyKey,
            payload: ['amount' => 10000],
        );

        // Second check with different payload should throw
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Idempotency conflict');

        $this->idempotencyService->check(
            operation: 'payment_init',
            idempotencyKey: $idempotencyKey,
            payload: ['amount' => 20000],
        );
    }

    #[Test]
    public function it_returns_null_for_different_operations(): void
    {
        $idempotencyKey = 'test-key-diff-ops';
        $payload = ['amount' => 10000];

        // Check for payment_init
        $result1 = $this->idempotencyService->check(
            operation: 'payment_init',
            idempotencyKey: $idempotencyKey,
            payload: $payload,
        );
        $this->assertNull($result1);

        // Check for wallet_debit should also return null
        $result2 = $this->idempotencyService->check(
            operation: 'wallet_debit',
            idempotencyKey: $idempotencyKey,
            payload: $payload,
        );
        $this->assertNull($result2);
    }

    #[Test]
    public function it_invalidates_idempotency_key(): void
    {
        $idempotencyKey = 'test-key-invalidate';
        $payload = ['amount' => 10000];
        $response = ['payment_id' => 'pay_123'];

        // Store operation
        $this->idempotencyService->check(
            operation: 'payment_init',
            idempotencyKey: $idempotencyKey,
            payload: $payload,
        );
        $this->idempotencyService->storeResponse(
            operation: 'payment_init',
            idempotencyKey: $idempotencyKey,
            response: $response,
        );

        // Invalidate
        $this->idempotencyService->invalidate(
            operation: 'payment_init',
            idempotencyKey: $idempotencyKey,
        );

        // Should return null after invalidation
        $result = $this->idempotencyService->check(
            operation: 'payment_init',
            idempotencyKey: $idempotencyKey,
            payload: $payload,
        );
        $this->assertNull($result);
    }

    #[Test]
    public function it_checks_if_key_exists(): void
    {
        $idempotencyKey = 'test-key-exists';

        // Should not exist initially
        $this->assertFalse(
            $this->idempotencyService->exists('payment_init', $idempotencyKey)
        );

        // Create key
        $this->idempotencyService->check(
            operation: 'payment_init',
            idempotencyKey: $idempotencyKey,
            payload: ['amount' => 10000],
        );

        // Should exist now
        $this->assertTrue(
            $this->idempotencyService->exists('payment_init', $idempotencyKey)
        );
    }

    #[Test]
    public function it_handles_concurrent_requests(): void
    {
        $idempotencyKey = 'test-key-concurrent';
        $payload = ['amount' => 10000];

        // Simulate concurrent requests
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->idempotencyService->check(
                operation: 'payment_init',
                idempotencyKey: $idempotencyKey,
                payload: $payload,
            );
        }

        // Only first should return null, rest should be idempotent hits
        $nullCount = count(array_filter($results, fn($r) => $r === null));
        $this->assertEquals(1, $nullCount);
    }
}
