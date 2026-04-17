<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment;

use App\Domains\Payment\Services\IdempotencyService;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * IdempotencyService Unit Tests.
 *
 * Tests atomic idempotency operations using Redis Lua scripts.
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
        $this->idempotencyService = new IdempotencyService(
            $this->redis,
            $this->app->make(\Psr\Log\LoggerInterface::class),
        );

        // Clear Redis before each test
        Redis::flushdb();
    }

    protected function tearDown(): void
    {
        Redis::flushdb();
        parent::tearDown();
    }

    public function test_check_or_mark_returns_null_on_first_call(): void
    {
        $correlationId = 'test_correlation_' . uniqid();
        $paymentId = 12345;

        $result = $this->idempotencyService->checkOrMark($correlationId, $paymentId);

        $this->assertNull($result, 'First call should return null (not previously processed)');
    }

    public function test_check_or_mark_returns_payment_id_on_duplicate(): void
    {
        $correlationId = 'test_correlation_' . uniqid();
        $paymentId = 12345;

        // First call
        $firstResult = $this->idempotencyService->checkOrMark($correlationId, $paymentId);
        $this->assertNull($firstResult);

        // Second call (duplicate)
        $secondResult = $this->idempotencyService->checkOrMark($correlationId, 99999);
        $this->assertSame($paymentId, $secondResult, 'Second call should return original payment ID');
    }

    public function test_check_or_mark_with_custom_ttl(): void
    {
        $correlationId = 'test_correlation_' . uniqid();
        $paymentId = 12345;
        $ttl = 10; // 10 seconds

        $this->idempotencyService->checkOrMark($correlationId, $paymentId, $ttl);

        // Wait for TTL to expire
        sleep(11);

        // Should be expired
        $result = $this->idempotencyService->check($correlationId);
        $this->assertNull($result, 'Key should expire after TTL');
    }

    public function test_mark_manually_sets_key(): void
    {
        $correlationId = 'test_correlation_' . uniqid();
        $paymentId = 54321;

        $this->idempotencyService->mark($correlationId, $paymentId);

        $result = $this->idempotencyService->check($correlationId);
        $this->assertSame($paymentId, $result);
    }

    public function test_check_returns_null_for_non_existent_key(): void
    {
        $correlationId = 'non_existent_' . uniqid();

        $result = $this->idempotencyService->check($correlationId);

        $this->assertNull($result);
    }

    public function test_check_returns_payment_id_for_existing_key(): void
    {
        $correlationId = 'test_correlation_' . uniqid();
        $paymentId = 99999;

        $this->idempotencyService->mark($correlationId, $paymentId);

        $result = $this->idempotencyService->check($correlationId);

        $this->assertSame($paymentId, $result);
    }

    public function test_delete_removes_key(): void
    {
        $correlationId = 'test_correlation_' . uniqid();
        $paymentId = 11111;

        $this->idempotencyService->mark($correlationId, $paymentId);
        $this->assertNotNull($this->idempotencyService->check($correlationId));

        $this->idempotencyService->delete($correlationId);

        $result = $this->idempotencyService->check($correlationId);
        $this->assertNull($result, 'Key should be deleted');
    }

    public function test_concurrent_operations_are_handled_correctly(): void
    {
        $correlationId = 'concurrent_' . uniqid();
        $paymentId = 22222;

        // Simulate concurrent calls
        $results = [];
        $processes = 5;

        for ($i = 0; $i < $processes; $i++) {
            $results[] = $this->idempotencyService->checkOrMark($correlationId, $paymentId);
        }

        // Only first call should return null, others should return payment ID
        $nullCount = array_filter($results, fn ($r) => $r === null);
        $paymentIdCount = array_filter($results, fn ($r) => $r === $paymentId);

        $this->assertCount(1, $nullCount, 'Only one call should return null');
        $this->assertCount($processes - 1, $paymentIdCount, 'All other calls should return payment ID');
    }

    public function test_different_correlation_ids_do_not_interfere(): void
    {
        $correlationId1 = 'test_1_' . uniqid();
        $correlationId2 = 'test_2_' . uniqid();
        $paymentId1 = 33333;
        $paymentId2 = 44444;

        $result1 = $this->idempotencyService->checkOrMark($correlationId1, $paymentId1);
        $result2 = $this->idempotencyService->checkOrMark($correlationId2, $paymentId2);

        $this->assertNull($result1);
        $this->assertNull($result2);

        $check1 = $this->idempotencyService->check($correlationId1);
        $check2 = $this->idempotencyService->check($correlationId2);

        $this->assertSame($paymentId1, $check1);
        $this->assertSame($paymentId2, $check2);
    }
}
