<?php declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\TestCase;

/**
 * Prometheus Configuration Unit Test
 * 
 * Tests Prometheus configuration without database dependency.
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class PrometheusConfigurationTest extends TestCase
{
    public function test_prometheus_storage_driver_is_redis(): void
    {
        $storageDriver = Config::get('prometheus.storage_driver');
        
        $this->assertEquals('redis', $storageDriver);
    }

    public function test_prometheus_namespace_is_catvrf(): void
    {
        $namespace = Config::get('prometheus.namespace');
        
        $this->assertEquals('catvrf', $namespace);
    }

    public function test_prometheus_route_is_enabled(): void
    {
        $routeEnabled = Config::get('prometheus.route.enabled');
        
        $this->assertTrue($routeEnabled);
    }

    public function test_prometheus_route_prefix_is_metrics(): void
    {
        $routePrefix = Config::get('prometheus.route.prefix');
        
        $this->assertEquals('metrics', $routePrefix);
    }

    public function test_prometheus_route_middleware_is_configured(): void
    {
        $middleware = Config::get('prometheus.route.middleware');
        
        $this->assertIsArray($middleware);
        $this->assertContains('auth:landlord', $middleware);
        $this->assertContains('throttle:metrics', $middleware);
    }

    public function test_prometheus_buckets_are_configured(): void
    {
        $buckets = Config::get('prometheus.buckets');
        
        $this->assertIsArray($buckets);
        $this->assertArrayHasKey('default', $buckets);
        $this->assertArrayHasKey('latency', $buckets);
        $this->assertArrayHasKey('duration', $buckets);
        $this->assertArrayHasKey('score', $buckets);
        $this->assertArrayHasKey('psi', $buckets);
        $this->assertArrayHasKey('quota', $buckets);
    }

    public function test_prometheus_collectors_is_empty_array(): void
    {
        $collectors = Config::get('prometheus.collectors');
        
        $this->assertIsArray($collectors);
        $this->assertEmpty($collectors);
    }

    public function test_prometheus_redis_connection_is_default(): void
    {
        $redisConnection = Config::get('prometheus.redis_connection');
        
        $this->assertEquals('default', $redisConnection);
    }

    public function test_prometheus_redis_prefix_is_set(): void
    {
        $redisPrefix = Config::get('prometheus.storage.redis.prefix');
        
        $this->assertEquals('prometheus:', $redisPrefix);
    }

    public function test_prometheus_label_cardinality_limits_are_configured(): void
    {
        $limits = Config::get('prometheus.label_cardinality_limits');
        
        $this->assertIsArray($limits);
        $this->assertArrayHasKey('max_labels_per_metric', $limits);
        $this->assertArrayHasKey('max_label_values_per_metric', $limits);
        $this->assertArrayHasKey('blocked_labels', $limits);
    }

    public function test_prometheus_blocked_labels_include_user_id(): void
    {
        $blockedLabels = Config::get('prometheus.label_cardinality_limits.blocked_labels');
        
        $this->assertIsArray($blockedLabels);
        $this->assertContains('user_id', $blockedLabels);
    }

    public function test_prometheus_blocked_labels_include_tenant_id(): void
    {
        $blockedLabels = Config::get('prometheus.label_cardinality_limits.blocked_labels');
        
        $this->assertIsArray($blockedLabels);
        $this->assertContains('tenant_id', $blockedLabels);
    }

    public function test_prometheus_blocked_labels_include_correlation_id(): void
    {
        $blockedLabels = Config::get('prometheus.label_cardinality_limits.blocked_labels');
        
        $this->assertIsArray($blockedLabels);
        $this->assertContains('correlation_id', $blockedLabels);
    }
}
