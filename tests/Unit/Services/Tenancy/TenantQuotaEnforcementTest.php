<?php declare(strict_types=1);

namespace Tests\Unit\Services\Tenancy;

use App\Exceptions\TenantQuotaExceededException;
use App\Services\Tenancy\TenantResourceLimiterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tenant Quota Enforcement Test
 *
 * Production 2026 CANON - Hard Quota Enforcement Tests
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class TenantQuotaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private TenantResourceLimiterService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TenantResourceLimiterService::class);
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $tenantId = 1;
        $this->service->resetUsage($tenantId);

        parent::tearDown();
    }

    public function test_ai_quota_throws_exception_when_exceeded(): void
    {
        $tenantId = 1;

        // Set a very low quota
        $this->service->setCustomQuota('ai_tokens', $tenantId, 100);

        $this->expectException(TenantQuotaExceededException::class);
        $this->expectExceptionMessage('Tenant 1 exceeded ai_tokens quota');

        $this->service->checkAIQuota($tenantId, 150);
    }

    public function test_ai_quota_passes_within_limit(): void
    {
        $tenantId = 1;

        // Set a reasonable quota
        $this->service->setCustomQuota('ai_tokens', $tenantId, 1000);

        // Should not throw
        $this->service->checkAIQuota($tenantId, 500);

        $this->assertTrue(true);
    }

    public function test_redis_quota_throws_exception_when_exceeded(): void
    {
        $tenantId = 1;

        // Set a very low quota
        $this->service->setCustomQuota('redis_ops', $tenantId, 5);

        $this->expectException(TenantQuotaExceededException::class);

        // Consume 5 operations
        for ($i = 0; $i < 5; $i++) {
            $this->service->checkRedisQuota($tenantId);
        }

        // 6th should fail
        $this->service->checkRedisQuota($tenantId);
    }

    public function test_redis_quota_passes_within_limit(): void
    {
        $tenantId = 1;

        $this->service->setCustomQuota('redis_ops', $tenantId, 100);

        for ($i = 0; $i < 50; $i++) {
            $this->service->checkRedisQuota($tenantId);
        }

        $this->assertTrue(true);
    }

    public function test_db_quota_throws_exception_when_exceeded(): void
    {
        $tenantId = 1;

        $this->service->setCustomQuota('db_queries', $tenantId, 10);

        $this->expectException(TenantQuotaExceededException::class);

        for ($i = 0; $i < 10; $i++) {
            $this->service->checkDBQuota($tenantId);
        }

        $this->service->checkDBQuota($tenantId);
    }

    public function test_storage_quota_throws_exception_when_exceeded(): void
    {
        $tenantId = 1;

        $this->service->setCustomQuota('storage_bytes', $tenantId, 1024); // 1KB

        $this->expectException(TenantQuotaExceededException::class);

        $this->service->checkStorageQuota($tenantId, 2048); // 2KB
    }

    public function test_vertical_quota_check(): void
    {
        $tenantId = 1;

        $this->service->setCustomQuota('vertical_medical_diagnosis', $tenantId, 5);

        // Should pass for first 5
        for ($i = 0; $i < 5; $i++) {
            $this->service->checkVerticalQuota($tenantId, 'medical', 'diagnosis');
        }

        // 6th should fail
        $this->expectException(TenantQuotaExceededException::class);
        $this->service->checkVerticalQuota($tenantId, 'medical', 'diagnosis');
    }

    public function test_quota_exception_contains_correct_details(): void
    {
        $tenantId = 1;

        $this->service->setCustomQuota('ai_tokens', $tenantId, 100);

        try {
            $this->service->checkAIQuota($tenantId, 150);
            $this->fail('Expected exception was not thrown');
        } catch (TenantQuotaExceededException $e) {
            $this->assertEquals($tenantId, $e->getTenantId());
            $this->assertEquals('ai_tokens', $e->getResourceType());
            $this->assertEquals(100, $e->getQuota());
            $this->assertEquals(150, $e->getRequested());
            $this->assertEquals(0, $e->getRemaining());
        }
    }

    public function test_check_quota_only_does_not_consume(): void
    {
        $tenantId = 1;

        $this->service->setCustomQuota('ai_tokens', $tenantId, 1000);

        $result1 = $this->service->checkQuotaOnly('ai_tokens', $tenantId, 200);
        $this->assertTrue($result1['allowed']);
        $this->assertEquals(0, $result1['used']);

        // Check again - should still be 0 since we didn't consume
        $result2 = $this->service->checkQuotaOnly('ai_tokens', $tenantId, 200);
        $this->assertEquals(0, $result2['used']);
    }

    public function test_race_condition_prevention(): void
    {
        $tenantId = 1;
        $this->service->setCustomQuota('ai_tokens', $tenantId, 100);

        // Simulate concurrent requests
        $passed = 0;
        $failed = 0;

        for ($i = 0; $i < 110; $i++) {
            try {
                $this->service->checkAIQuota($tenantId, 1);
                $passed++;
            } catch (TenantQuotaExceededException $e) {
                $failed++;
            }
        }

        // Should have exactly 100 passed and 10 failed
        $this->assertEquals(100, $passed);
        $this->assertEquals(10, $failed);
    }

    public function test_quota_persistence_after_flush(): void
    {
        $tenantId = 1;
        $this->service->setCustomQuota('ai_tokens', $tenantId, 1000);

        // Consume some quota
        $this->service->checkAIQuota($tenantId, 500);

        $stats = $this->service->getQuotaStats($tenantId);
        $this->assertEquals(500, $stats['ai_tokens']['used']);

        // Flush to database
        $persistenceService = app(\App\Services\Tenancy\TenantQuotaPersistenceService::class);
        $persistenceService->ensureTableExists();
        $flushed = $persistenceService->flushToDatabase();

        $this->assertGreaterThan(0, $flushed);

        // Redis should be reset after flush
        $statsAfterFlush = $this->service->getQuotaStats($tenantId);
        $this->assertEquals(0, $statsAfterFlush['ai_tokens']['used']);
    }

    public function test_quota_plan_application(): void
    {
        $tenantId = 1;
        $planService = app(\App\Services\Tenancy\TenantQuotaPlanService::class);

        $planService->applyPlan($tenantId, 'starter');

        $stats = $this->service->getQuotaStats($tenantId);

        $this->assertEquals(100000, $stats['ai_tokens']['quota']);
        $this->assertEquals(10000, $stats['redis_ops']['quota']);
    }

    public function test_quota_plan_upgrade(): void
    {
        $tenantId = 1;
        $planService = app(\App\Services\Tenancy\TenantQuotaPlanService::class);

        $planService->applyPlan($tenantId, 'free');
        $statsFree = $this->service->getQuotaStats($tenantId);
        $this->assertEquals(10000, $statsFree['ai_tokens']['quota']);

        $planService->upgradePlan($tenantId, 'pro');
        $statsPro = $this->service->getQuotaStats($tenantId);
        $this->assertEquals(1000000, $statsPro['ai_tokens']['quota']);
    }
}
