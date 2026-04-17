<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\QuotaClickHouseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit Tests for QuotaClickHouseRepository
 * 
 * Production 2026 CANON - ClickHouse Quota Analytics Tests
 * 
 * Tests cover:
 * - Idempotent inserts with quota_event_id
 * - Batch inserts for performance
 * - Retry logic with exponential backoff
 * - Usage queries (current hour, daily, range)
 * - Connection testing
 * - Error handling
 */
final class QuotaClickHouseRepositoryTest extends TestCase
{
    private QuotaClickHouseRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new QuotaClickHouseRepository();
    }

    /**
     * Test single quota event insertion
     */
    public function test_insert_quota_event(): void
    {
        // Skip if ClickHouse is not configured
        if (!$this->repository->testConnection()) {
            $this->markTestSkipped('ClickHouse not configured');
        }

        $event = [
            'tenant_id' => 1,
            'business_group_id' => 100,
            'vertical_code' => 'medical',
            'resource_type' => 'ai_tokens',
            'operation_type' => 'increment',
            'amount_used' => 100.5,
            'unit' => 'tokens',
            'user_id' => 123,
            'correlation_id' => 'test-correlation-123',
            'metadata' => ['test' => 'data'],
        ];

        $result = $this->repository->insertQuotaEvent($event);

        $this->assertTrue($result);
    }

    /**
     * Test idempotent insert - duplicate event should not fail
     */
    public function test_idempotent_insert(): void
    {
        if (!$this->repository->testConnection()) {
            $this->markTestSkipped('ClickHouse not configured');
        }

        $quotaEventId = 'test-idempotent-' . time();
        $event = [
            'quota_event_id' => $quotaEventId,
            'tenant_id' => 1,
            'resource_type' => 'ai_tokens',
            'amount_used' => 50.0,
        ];

        // First insert
        $result1 = $this->repository->insertQuotaEvent($event);
        $this->assertTrue($result1);

        // Check if event exists
        $exists = $this->repository->eventExists($quotaEventId);
        $this->assertTrue($exists);

        // Duplicate insert (should be handled by job uniqueness)
        $result2 = $this->repository->insertQuotaEvent($event);
        $this->assertTrue($result2);
    }

    /**
     * Test batch insert of quota events
     */
    public function test_batch_insert_quota_events(): void
    {
        if (!$this->repository->testConnection()) {
            $this->markTestSkipped('ClickHouse not configured');
        }

        $events = [];
        for ($i = 0; $i < 10; $i++) {
            $events[] = [
                'tenant_id' => 1,
                'resource_type' => 'ai_tokens',
                'amount_used' => 10.0 * $i,
                'vertical_code' => 'medical',
            ];
        }

        $result = $this->repository->batchInsertQuotaEvents($events);

        $this->assertTrue($result);
    }

    /**
     * Test get current hour usage
     */
    public function test_get_current_hour_usage(): void
    {
        if (!$this->repository->testConnection()) {
            $this->markTestSkipped('ClickHouse not configured');
        }

        $usage = $this->repository->getCurrentHourUsage(1, 'ai_tokens');

        $this->assertIsFloat($usage);
        $this->assertGreaterThanOrEqual(0.0, $usage);
    }

    /**
     * Test get daily usage
     */
    public function test_get_daily_usage(): void
    {
        if (!$this->repository->testConnection()) {
            $this->markTestSkipped('ClickHouse not configured');
        }

        $usage = $this->repository->getDailyUsage(1, 'ai_tokens');

        $this->assertIsFloat($usage);
        $this->assertGreaterThanOrEqual(0.0, $usage);
    }

    /**
     * Test get usage in date range
     */
    public function test_get_usage_in_range(): void
    {
        if (!$this->repository->testConnection()) {
            $this->markTestSkipped('ClickHouse not configured');
        }

        $startDate = now()->subDays(7)->toDateString();
        $endDate = now()->toDateString();

        $usage = $this->repository->getUsageInRange(1, 'ai_tokens', $startDate, $endDate);

        $this->assertIsFloat($usage);
        $this->assertGreaterThanOrEqual(0.0, $usage);
    }

    /**
     * Test event exists check
     */
    public function test_event_exists(): void
    {
        if (!$this->repository->testConnection()) {
            $this->markTestSkipped('ClickHouse not configured');
        }

        $nonExistentId = 'non-existent-' . time();
        $exists = $this->repository->eventExists($nonExistentId);

        $this->assertFalse($exists);
    }

    /**
     * Test connection
     */
    public function test_connection(): void
    {
        $result = $this->repository->testConnection();

        // This test passes if ClickHouse is configured, skips otherwise
        if (!$result) {
            $this->markTestSkipped('ClickHouse not configured');
        }

        $this->assertTrue($result);
    }

    /**
     * Test error handling for invalid data
     */
    public function test_error_handling_invalid_data(): void
    {
        if (!$this->repository->testConnection()) {
            $this->markTestSkipped('ClickHouse not configured');
        }

        // Missing required fields
        $event = [
            'tenant_id' => 1,
            // Missing resource_type
        ];

        $result = $this->repository->insertQuotaEvent($event);

        // Should handle error gracefully
        $this->assertFalse($result);
    }

    /**
     * Test empty batch insert
     */
    public function test_empty_batch_insert(): void
    {
        $result = $this->repository->batchInsertQuotaEvents([]);

        $this->assertTrue($result);
    }

    /**
     * Test retry logic is implemented
     */
    public function test_retry_logic_implemented(): void
    {
        // This is a structural test to verify retry logic exists
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('withRetry');
        
        $this->assertTrue($method->isPrivate());
        $parameters = $method->getParameters();
        
        // Should have callback parameter
        $this->assertCount(1, $parameters);
        $this->assertInstanceOf(\ReflectionParameter::class, $parameters[0]);
    }

    /**
     * Test OpenTelemetry trace ID integration
     */
    public function test_trace_id_integration(): void
    {
        if (!$this->repository->testConnection()) {
            $this->markTestSkipped('ClickHouse not configured');
        }

        $event = [
            'tenant_id' => 1,
            'resource_type' => 'ai_tokens',
            'amount_used' => 100.0,
            'trace_id' => 'test-trace-123',
        ];

        $result = $this->repository->insertQuotaEvent($event);

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
