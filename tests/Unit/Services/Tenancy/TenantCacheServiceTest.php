<?php declare(strict_types=1);

namespace Tests\Unit\Services\Tenancy;

use App\Services\Tenancy\TenantCacheService;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tests\TestCase;

/**
 * Tenant Cache Service Test
 *
 * Production 2026 CANON - Multi-Tenant Security Tests
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class TenantCacheServiceTest extends TestCase
{
    private TenantCacheService $service;
    private CacheManager $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = app(CacheManager::class);
        $logger = app(LoggerInterface::class);

        $this->service = new TenantCacheService(
            $this->cache,
            $logger
        );
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->cache->forget('tenant:1:test_key');
        $this->cache->forget('tenant:2:test_key');

        parent::tearDown();
    }

    public function test_get_prefixed_key(): void
    {
        $tenantId = 1;
        $key = 'test_key';

        $prefixedKey = $this->service->getPrefixedKey($tenantId, $key);

        $this->assertEquals('tenant:1:test_key', $prefixedKey);
    }

    public function test_get_prefixed_key_throws_exception_without_tenant(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tenant ID is required for cache operations in tenant context');

        $this->service->getPrefixedKey(null, 'test_key');
    }

    public function test_put_and_get_with_tenant_prefix(): void
    {
        $tenantId = 1;
        $key = 'test_key';
        $value = 'test_value';

        $this->service->put($tenantId, $key, $value, 60);
        $result = $this->service->get($tenantId, $key);

        $this->assertEquals($value, $result);
    }

    public function test_get_returns_default_when_not_found(): void
    {
        $tenantId = 1;
        $key = 'non_existent_key';
        $default = 'default_value';

        $result = $this->service->get($tenantId, $key, $default);

        $this->assertEquals($default, $result);
    }

    public function test_remember_with_tenant_prefix(): void
    {
        $tenantId = 1;
        $key = 'remember_key';
        $value = 'remembered_value';

        $result = $this->service->remember($tenantId, $key, 60, function () use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result);

        // Verify it's cached
        $cached = $this->service->get($tenantId, $key);
        $this->assertEquals($value, $cached);
    }

    public function test_has_checks_key_existence(): void
    {
        $tenantId = 1;
        $key = 'has_key';

        $this->assertFalse($this->service->has($tenantId, $key));

        $this->service->put($tenantId, $key, 'value', 60);

        $this->assertTrue($this->service->has($tenantId, $key));
    }

    public function test_forget_removes_key(): void
    {
        $tenantId = 1;
        $key = 'forget_key';

        $this->service->put($tenantId, $key, 'value', 60);
        $this->assertTrue($this->service->has($tenantId, $key));

        $this->service->forget($tenantId, $key);
        $this->assertFalse($this->service->has($tenantId, $key));
    }

    public function test_cache_isolation_between_tenants(): void
    {
        $tenant1 = 1;
        $tenant2 = 2;
        $key = 'isolation_key';

        // Set different values for different tenants
        $this->service->put($tenant1, $key, 'tenant1_value', 60);
        $this->service->put($tenant2, $key, 'tenant2_value', 60);

        // Verify isolation
        $value1 = $this->service->get($tenant1, $key);
        $value2 = $this->service->get($tenant2, $key);

        $this->assertEquals('tenant1_value', $value1);
        $this->assertEquals('tenant2_value', $value2);
        $this->assertNotEquals($value1, $value2);
    }

    public function test_increment_with_tenant_prefix(): void
    {
        $tenantId = 1;
        $key = 'increment_key';

        $this->service->put($tenantId, $key, 5, 60);
        $result = $this->service->increment($tenantId, $key, 3);

        $this->assertEquals(8, $this->service->get($tenantId, $key));
    }

    public function test_decrement_with_tenant_prefix(): void
    {
        $tenantId = 1;
        $key = 'decrement_key';

        $this->service->put($tenantId, $key, 10, 60);
        $result = $this->service->decrement($tenantId, $key, 3);

        $this->assertEquals(7, $this->service->get($tenantId, $key));
    }

    public function test_get_tags_with_tenant_prefix(): void
    {
        $tenantId = 1;
        $tags = ['tag1', 'tag2'];

        $prefixedTags = $this->service->getTags($tenantId, $tags);

        $this->assertEquals(['tenant:1:tag1', 'tenant:1:tag2'], $prefixedTags);
    }

    public function test_many_operations(): void
    {
        $tenantId = 1;
        $keys = ['key1', 'key2', 'key3'];
        $values = ['value1', 'value2', 'value3'];

        $this->service->putMany($tenantId, array_combine($keys, $values), 60);
        $results = $this->service->many($tenantId, $keys);

        $this->assertEquals($values, array_values($results));
    }
}
