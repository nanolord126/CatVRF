<?php declare(strict_types=1);

namespace Tests\Unit\Services\Tenancy;

use App\Services\Tenancy\TenantResourceLimiterService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\LogManager;
use Tests\TestCase;

/**
 * Tenant Resource Limiter Service Test
 *
 * Production 2026 CANON - Multi-Tenant Security Tests
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class TenantResourceLimiterServiceTest extends TestCase
{
    private TenantResourceLimiterService $service;
    private RedisFactory $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = app(RedisFactory::class);
        $config = app(ConfigRepository::class);
        $logger = app(LogManager::class);

        $this->service = new TenantResourceLimiterService(
            $this->redis,
            $config,
            $logger
        );
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->redis->connection()->del('tenant:quota:ai_tokens:1');
        $this->redis->connection()->del('tenant:quota:redis_ops:1');
        $this->redis->connection()->del('tenant:quota:db_queries:1');
        $this->redis->connection()->del('tenant:quota:storage_bytes:1');

        parent::tearDown();
    }

    public function test_check_ai_quota_allows_within_limit(): void
    {
        $tenantId = 1;
        $tokensRequested = 1000;

        $result = $this->service->checkAIQuota($tenantId, $tokensRequested);

        $this->assertTrue($result);
    }

    public function test_check_ai_quota_blocks_when_exceeded(): void
    {
        $tenantId = 1;

        // Set quota to a low value for testing
        $this->service->setCustomQuota('ai_tokens', $tenantId, 100);

        // Try to consume more than quota
        $result = $this->service->checkAIQuota($tenantId, 200);

        $this->assertFalse($result);
    }

    public function test_check_redis_quota_allows_within_limit(): void
    {
        $tenantId = 1;

        $result = $this->service->checkRedisQuota($tenantId);

        $this->assertTrue($result);
    }

    public function test_check_redis_quota_blocks_when_exceeded(): void
    {
        $tenantId = 1;

        // Set quota to a low value
        $this->service->setCustomQuota('redis_ops', $tenantId, 10);

        // Consume quota
        for ($i = 0; $i < 10; $i++) {
            $this->service->checkRedisQuota($tenantId);
        }

        // Next request should be blocked
        $result = $this->service->checkRedisQuota($tenantId);

        $this->assertFalse($result);
    }

    public function test_check_db_quota_allows_within_limit(): void
    {
        $tenantId = 1;

        $result = $this->service->checkDBQuota($tenantId);

        $this->assertTrue($result);
    }

    public function test_check_storage_quota_allows_within_limit(): void
    {
        $tenantId = 1;
        $bytesRequested = 1024 * 1024; // 1MB

        $result = $this->service->checkStorageQuota($tenantId, $bytesRequested);

        $this->assertTrue($result);
    }

    public function test_check_storage_quota_blocks_when_exceeded(): void
    {
        $tenantId = 1;

        // Set quota to 1MB
        $this->service->setCustomQuota('storage_bytes', $tenantId, 1024 * 1024);

        // Try to use 2MB
        $result = $this->service->checkStorageQuota($tenantId, 2 * 1024 * 1024);

        $this->assertFalse($result);
    }

    public function test_set_custom_quota(): void
    {
        $tenantId = 1;
        $resourceType = 'ai_tokens';
        $quota = 50000;

        $this->service->setCustomQuota($resourceType, $tenantId, $quota);

        $stats = $this->service->getQuotaStats($tenantId);

        $this->assertEquals($quota, $stats[$resourceType]['limit']);
    }

    public function test_get_quota_stats(): void
    {
        $tenantId = 1;

        $stats = $this->service->getQuotaStats($tenantId);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('ai_tokens', $stats);
        $this->assertArrayHasKey('redis_ops', $stats);
        $this->assertArrayHasKey('db_queries', $stats);
        $this->assertArrayHasKey('storage_bytes', $stats);

        foreach ($stats as $resource => $data) {
            $this->assertArrayHasKey('used', $data);
            $this->assertArrayHasKey('limit', $data);
            $this->assertArrayHasKey('percentage', $data);
            $this->assertArrayHasKey('remaining', $data);
        }
    }

    public function test_reset_usage(): void
    {
        $tenantId = 1;

        // Consume some quota
        $this->service->checkAIQuota($tenantId, 100);

        // Reset
        $this->service->resetUsage($tenantId, 'ai_tokens');

        // Check if usage is reset
        $stats = $this->service->getQuotaStats($tenantId);
        $this->assertEquals(0, $stats['ai_tokens']['used']);
    }

    public function test_is_rate_limited(): void
    {
        $tenantId = 1;
        $operation = 'test_operation';

        // First request should not be rate limited
        $result1 = $this->service->isRateLimited($tenantId, $operation);
        $this->assertFalse($result1);

        // Set a very low limit for testing
        config(['tenant.rate_limits.test_operation.limit' => 5]);

        // Make 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->service->isRateLimited($tenantId, $operation);
        }

        // 6th request should be rate limited
        $result2 = $this->service->isRateLimited($tenantId, $operation);
        $this->assertTrue($result2);
    }
}
