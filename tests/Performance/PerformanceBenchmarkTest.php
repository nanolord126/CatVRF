<?php

declare(strict_types=1);

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\BusinessGroup;
use App\Services\Security\VpnDetectionService;
use App\Services\Security\ResidentialProxyDetectionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Performance Benchmark Test 2026
 * 
 * Measures critical endpoints and services before/after optimization.
 * Target TTFB < 80ms for 95th percentile.
 * 
 * Benchmarks:
 * - Tenant resolution (tenancy bootstrap)
 * - User lookup with eager loading
 * - VPN detection with cache
 * - Residential proxy detection with cache
 * - Filament resource queries
 */
uses(TestCase::class);

beforeEach(function () {
    // Clear cache before each benchmark
    Cache::flush();
});

it('benchmarks tenant resolution time', function () {
    $tenant = Tenant::factory()->create();
    
    $time = benchmark(function () use ($tenant) {
        tenancy()->initialize($tenant);
    }, iterations: 100);
    
    expect($time)->toBeLessThan(50); // < 50ms for tenant resolution
});

it('benchmarks user lookup with eager loading', function () {
    $tenant = Tenant::factory()->create();
    $users = User::factory()->count(10)->for($tenant)->create();
    
    $time = benchmark(function () use ($tenant) {
        User::where('tenant_id', $tenant->id)
            ->with(['tenant', 'businessGroup'])
            ->select(['id', 'name', 'email', 'tenant_id', 'business_group_id', 'created_at'])
            ->get();
    }, iterations: 100);
    
    expect($time)->toBeLessThan(30); // < 30ms for user lookup
});

it('benchmarks business group query with eager loading', function () {
    $tenant = Tenant::factory()->create();
    $businessGroups = BusinessGroup::factory()->count(10)->for($tenant)->create();
    
    $time = benchmark(function () use ($tenant) {
        BusinessGroup::where('tenant_id', $tenant->id)
            ->with(['tenant', 'parentBusinessGroup'])
            ->select(['id', 'name', 'inn', 'tenant_id', 'parent_id', 'created_at'])
            ->get();
    }, iterations: 100);
    
    expect($time)->toBeLessThan(25); // < 25ms for business group query
});

it('benchmarks VPN detection with cache', function () {
    $service = app(VpnDetectionService::class);
    $request = createRequest(ip: '8.8.8.8');
    
    // First call (no cache)
    $timeUncached = benchmark(function () use ($service, $request) {
        $service->detect($request);
    }, iterations: 10);
    
    // Second call (from cache)
    $timeCached = benchmark(function () use ($service, $request) {
        $service->detect($request);
    }, iterations: 100);
    
    // Cached should be at least 80% faster
    expect($timeCached)->toBeLessThan($timeUncached * 0.2);
    expect($timeCached)->toBeLessThan(15); // < 15ms for cached VPN detection
});

it('benchmarks residential proxy detection with cache', function () {
    $service = app(ResidentialProxyDetectionService::class);
    $request = createRequest(ip: '8.8.8.8');
    
    // First call (no cache)
    $timeUncached = benchmark(function () use ($service, $request) {
        $service->detect($request);
    }, iterations: 10);
    
    // Second call (from cache)
    $timeCached = benchmark(function () use ($service, $request) {
        $service->detect($request);
    }, iterations: 100);
    
    // Cached should be at least 80% faster
    expect($timeCached)->toBeLessThan($timeUncached * 0.2);
    expect($timeCached)->toBeLessThan(15); // < 15ms for cached proxy detection
});

it('benchmarks cache operations with Redis', function () {
    $key = 'benchmark_test_key';
    $value = ['test' => 'data', 'nested' => ['value' => 123]];
    
    $writeTime = benchmark(function () use ($key, $value) {
        Cache::put($key, $value, 3600);
    }, iterations: 1000);
    
    $readTime = benchmark(function () use ($key) {
        Cache::get($key);
    }, iterations: 1000);
    
    expect($writeTime)->toBeLessThan(5); // < 5ms for cache write
    expect($readTime)->toBeLessThan(2); // < 2ms for cache read
});

it('benchmarks database query with composite index', function () {
    $tenant = Tenant::factory()->create();
    $users = User::factory()->count(100)->for($tenant)->create();
    
    // Query using composite index
    $time = benchmark(function () use ($tenant) {
        DB::table('users')
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }, iterations: 100);
    
    expect($time)->toBeLessThan(20); // < 20ms for indexed query
});

it('benchmarks cache tags invalidation', function () {
    $tenantId = 1;
    
    $writeTime = benchmark(function () use ($tenantId) {
        Cache::tags(['tenant:' . $tenantId, 'geo'])
            ->put('test_key', 'test_value', 3600);
    }, iterations: 100);
    
    $invalidateTime = benchmark(function () use ($tenantId) {
        Cache::tags(['tenant:' . $tenantId])->flush();
    }, iterations: 100);
    
    expect($writeTime)->toBeLessThan(10);
    expect($invalidateTime)->BeLessThan(20);
});

it('measures TTFB for critical endpoints', function () {
    $response = $this->get('/api/health');
    
    $ttfb = $response->headers->get('X-Response-Time') ?? 0;
    
    // TTFB should be < 80ms for health check
    expect($ttfb)->toBeLessThan(80);
});

/**
 * Helper function to create a test request
 */
function createRequest(string $ip = '127.0.0.1'): Illuminate\Http\Request
{
    return Illuminate\Http\Request::create('/', 'GET', [], [], [], [
        'REMOTE_ADDR' => $ip,
    ]);
}
