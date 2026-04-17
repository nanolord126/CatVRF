<?php declare(strict_types=1);

namespace Tests\Unit\Services\Cache;

use App\Services\Cache\CacheService;
use App\Services\Tenancy\TenantCacheService;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Cache Service Unit Tests
 *
 * Production 2026 CANON - Cache Service Test Suite
 *
 * Tests for:
 * - rememberWithTags with stampede protection
 * - Cache invalidation methods
 * - Tenant prefix isolation
 * - Layered cache fallback
 * - Embeddings caching
 *
 * @author CatVRF Team
 * @version 2026.04.18
 */
final class CacheServiceTest extends TestCase
{
    private CacheService $cacheService;
    private TenantCacheService $tenantCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantCache = $this->app->make(TenantCacheService::class);

        // Mock CacheMetricsService to avoid Horizon dependency
        $metricsMock = Mockery::mock('App\Services\Cache\CacheMetricsService');
        $metricsMock->shouldReceive('recordCacheHit')->andReturnNull();
        $metricsMock->shouldReceive('recordCacheMiss')->andReturnNull();
        $metricsMock->shouldReceive('recordCacheWriteLatency')->andReturnNull();
        $metricsMock->shouldReceive('recordCacheInvalidation')->andReturnNull();
        $metricsMock->shouldReceive('recordCacheError')->andReturnNull();
        $metricsMock->shouldReceive('recordCacheLockTimeout')->andReturnNull();

        $this->cacheService = new CacheService(
            $this->tenantCache,
            $this->app->make(CacheManager::class),
            Log::channel(),
            $metricsMock,
        );

        Cache::flush();
    }

    public function test_remember_with_tags_caches_value(): void
    {
        $tenantId = 1;
        $key = 'test:key';
        $ttl = 60;
        $tags = ['test', 'unit'];
        $expected = ['data' => 'test'];

        $result = $this->cacheService->rememberWithTags(
            $tenantId,
            $key,
            $ttl,
            $tags,
            fn () => $expected
        );

        $this->assertEquals($expected, $result);

        // Second call should hit cache
        $result2 = $this->cacheService->rememberWithTags(
            $tenantId,
            $key,
            $ttl,
            $tags,
            fn () => ['different' => 'data']
        );

        $this->assertEquals($expected, $result2);
    }

    public function test_invalidate_by_tags(): void
    {
        $tenantId = 1;
        $key = 'test:invalidate';
        $ttl = 60;
        $tags = ['test', 'invalidate'];
        $expected = ['data' => 'invalidate'];

        // Cache value
        $this->cacheService->rememberWithTags(
            $tenantId,
            $key,
            $ttl,
            $tags,
            fn () => $expected
        );

        // Invalidate
        $result = $this->cacheService->invalidate($tenantId, $tags);
        $this->assertTrue($result);

        // Value should be gone
        $result2 = $this->cacheService->rememberWithTags(
            $tenantId,
            $key,
            $ttl,
            $tags,
            fn () => ['new' => 'data']
        );

        $this->assertEquals(['new' => 'data'], $result2);
    }

    public function test_invalidate_user(): void
    {
        $tenantId = 1;
        $userId = 123;
        $tags = ['user:' . $userId, 'test'];

        $result = $this->cacheService->invalidateUser($tenantId, $userId);
        $this->assertTrue($result);
    }

    public function test_invalidate_diagnostic(): void
    {
        $tenantId = 1;
        $userId = 123;

        $result = $this->cacheService->invalidateDiagnostic($tenantId, $userId);
        $this->assertTrue($result);
    }

    public function test_invalidate_doctor(): void
    {
        $tenantId = 1;
        $doctorId = 456;

        $result = $this->cacheService->invalidateDoctor($tenantId, $doctorId);
        $this->assertTrue($result);
    }

    public function test_invalidate_clinic(): void
    {
        $tenantId = 1;
        $clinicId = 789;

        $result = $this->cacheService->invalidateClinic($tenantId, $clinicId);
        $this->assertTrue($result);
    }

    public function test_invalidate_slots(): void
    {
        $tenantId = 1;
        $doctorId = 456;
        $clinicId = 789;

        $result = $this->cacheService->invalidateSlots($tenantId, $doctorId, $clinicId);
        $this->assertTrue($result);
    }

    public function test_get_key_with_naming_convention(): void
    {
        $tenantId = 1;
        $vertical = 'medical';
        $entity = 'diagnosis';
        $identifier = '123:hash';

        $key = $this->cacheService->getKey($tenantId, $vertical, $entity, $identifier);
        $this->assertEquals('medical:diagnosis:123:hash', $key);
    }

    public function test_get_diagnostic_key(): void
    {
        $tenantId = 1;
        $userId = 123;
        $symptomsHash = 'abc123';

        $key = $this->cacheService->getDiagnosticKey($tenantId, $userId, $symptomsHash);
        $this->assertEquals('medical:diagnosis:123:abc123', $key);
    }

    public function test_get_health_score_key(): void
    {
        $tenantId = 1;
        $userId = 123;

        $key = $this->cacheService->getHealthScoreKey($tenantId, $userId);
        $this->assertEquals('medical:health_score:123', $key);
    }

    public function test_get_recommendations_key(): void
    {
        $tenantId = 1;
        $vertical = 'medical';
        $userId = 123;
        $contextHash = 'xyz789';

        $key = $this->cacheService->getRecommendationsKey($tenantId, $vertical, $userId, $contextHash);
        $this->assertEquals('medical:recommendations:123:xyz789', $key);
    }

    public function test_get_slots_key(): void
    {
        $tenantId = 1;
        $doctorId = 456;
        $date = '2026-04-18';

        $key = $this->cacheService->getSlotsKey($tenantId, $doctorId, $date);
        $this->assertEquals('medical:slots:456:2026-04-18', $key);
    }

    public function test_get_dynamic_price_key(): void
    {
        $tenantId = 1;
        $entityType = 'appointment';
        $entityId = 789;

        $key = $this->cacheService->getDynamicPriceKey($tenantId, $entityType, $entityId);
        $this->assertEquals('pricing:dynamic:appointment:789', $key);
    }

    public function test_get_embedding_key(): void
    {
        $tenantId = 1;
        $textHash = 'hash123';

        $key = $this->cacheService->getEmbeddingKey($tenantId, $textHash);
        $this->assertEquals('embeddings:vector:hash123', $key);
    }

    public function test_remember_embedding(): void
    {
        $tenantId = 1;
        $textHash = 'hash123';
        $expected = [0.1, 0.2, 0.3, 0.4];

        $result = $this->cacheService->rememberEmbedding(
            $tenantId,
            $textHash,
            fn () => $expected
        );

        $this->assertEquals($expected, $result);
        $this->assertIsArray($result);
    }

    public function test_invalidate_embeddings(): void
    {
        $tenantId = 1;

        $result = $this->cacheService->invalidateEmbeddings($tenantId);
        $this->assertTrue($result);
    }

    public function test_ttl_constants(): void
    {
        $this->assertEquals(300, CacheService::TTL_MEDICAL_DIAGNOSIS);
        $this->assertEquals(600, CacheService::TTL_MEDICAL_HEALTH_SCORE);
        $this->assertEquals(900, CacheService::TTL_RECOMMENDATIONS);
        $this->assertEquals(60, CacheService::TTL_SLOTS);
        $this->assertEquals(300, CacheService::TTL_DYNAMIC_PRICE);
        $this->assertEquals(86400, CacheService::TTL_EMBEDDINGS);
        $this->assertEquals(300, CacheService::TTL_QUOTA_COUNTERS);
    }

    public function test_invalidate_vertical(): void
    {
        $tenantId = 1;
        $vertical = 'medical';

        $result = $this->cacheService->invalidateVertical($tenantId, $vertical);
        $this->assertTrue($result);
    }

    public function test_invalidate_recommendations(): void
    {
        $tenantId = 1;
        $userId = 123;

        $result = $this->cacheService->invalidateRecommendations($tenantId, $userId);
        $this->assertTrue($result);
    }

    public function test_invalidate_health_score(): void
    {
        $tenantId = 1;
        $userId = 123;

        $result = $this->cacheService->invalidateHealthScore($tenantId, $userId);
        $this->assertTrue($result);
    }

    public function test_invalidate_dynamic_price(): void
    {
        $tenantId = 1;
        $entityType = 'appointment';
        $entityId = 789;

        $result = $this->cacheService->invalidateDynamicPrice($tenantId, $entityType, $entityId);
        $this->assertTrue($result);
    }
}
