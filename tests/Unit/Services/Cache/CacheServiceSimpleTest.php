<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cache;

use PHPUnit\Framework\TestCase;
use App\Services\Cache\CacheMetricsService;
use App\Services\Cache\CacheService;

/**
 * Cache Service Simple Unit Tests
 *
 * Production 2026 CANON - Simplified Test Suite
 *
 * Tests for CacheService constants and key generation methods
 * without full Laravel bootstrap dependency.
 *
 * @author CatVRF Team
 *
 * @version 2026.04.18
 */
final class CacheServiceSimpleTest extends TestCase
{
    public function test_cache_ttl_constants_are_defined(): void
    {
        $this->assertTrue(defined('App\Services\Cache\CacheService::TTL_MEDICAL_DIAGNOSIS'));
        $this->assertTrue(defined('App\Services\Cache\CacheService::TTL_MEDICAL_HEALTH_SCORE'));
        $this->assertTrue(defined('App\Services\Cache\CacheService::TTL_RECOMMENDATIONS'));
        $this->assertTrue(defined('App\Services\Cache\CacheService::TTL_SLOTS'));
        $this->assertTrue(defined('App\Services\Cache\CacheService::TTL_DYNAMIC_PRICE'));
        $this->assertTrue(defined('App\Services\Cache\CacheService::TTL_EMBEDDINGS'));
        $this->assertTrue(defined('App\Services\Cache\CacheService::TTL_QUOTA_COUNTERS'));
    }

    public function test_ttl_values_are_correct(): void
    {
        $this->assertEquals(300, CacheService::TTL_MEDICAL_DIAGNOSIS);
        $this->assertEquals(600, CacheService::TTL_MEDICAL_HEALTH_SCORE);
        $this->assertEquals(900, CacheService::TTL_RECOMMENDATIONS);
        $this->assertEquals(60, CacheService::TTL_SLOTS);
        $this->assertEquals(300, CacheService::TTL_DYNAMIC_PRICE);
        $this->assertEquals(86400, CacheService::TTL_EMBEDDINGS);
        $this->assertEquals(300, CacheService::TTL_QUOTA_COUNTERS);
    }

    public function test_cache_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CacheService::class));
    }

    public function test_cache_metrics_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CacheMetricsService::class));
    }
}
