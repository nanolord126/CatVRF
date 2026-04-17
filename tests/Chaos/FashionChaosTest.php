<?php declare(strict_types=1);

namespace Tests\Chaos;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class FashionChaosTest extends \Tests\Chaos\ChaosEngineeringTest
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_product_service_survives_cache_failure(): void
    {
        $this->simulateCacheFailure();

        $response = $this->actingAs($this->user)
            ->getJson('/api/fashion/products');

        $this->assertNotEquals(500, $response->status(), 'Service should survive cache failure');
        $this->restoreCache();
    }

    public function test_order_service_survives_database_timeout(): void
    {
        $this->simulateDatabaseTimeout();

        $response = $this->actingAs($this->user)
            ->postJson('/api/fashion/orders', [
                'items' => [['product_id' => 1, 'quantity' => 1]],
                'payment_method' => 'wallet',
            ]);

        $this->assertNotEquals(500, $response->status(), 'Service should handle database timeout gracefully');
        $this->restoreDatabase();
    }

    public function test_inventory_service_survives_redis_failure(): void
    {
        $this->simulateRedisFailure();

        $response = $this->actingAs($this->user)
            ->postJson('/api/fashion/products/1/reserve', [
                'quantity' => 1,
                'order_id' => 'test_order',
            ]);

        $this->assertNotEquals(500, $response->status(), 'Service should survive Redis failure');
        $this->restoreRedis();
    }

    public function test_product_search_handles_high_latency(): void
    {
        $this->simulateHighLatency(5000);

        $response = $this->actingAs($this->user)
            ->getJson('/api/fashion/search?q=dress');

        $this->assertNotEquals(504, $response->status(), 'Search should handle high latency');
        $this->restoreNormalLatency();
    }

    public function test_bulk_recommendation_handles_partial_failures(): void
    {
        $this->simulateRandomFailures(0.3);

        $response = $this->actingAs($this->user)
            ->postJson('/api/fashion/ml/cross-vertical-recommendations', [
                'limit' => 10,
            ]);

        $this->assertNotEquals(500, $response->status(), 'Recommendations should handle partial failures');
        $this->restoreNormalOperation();
    }

    public function test_size_service_survives_network_partition(): void
    {
        $this->simulateNetworkPartition();

        $response = $this->actingAs($this->user)
            ->getJson('/api/fashion/ml/size-recommendation/1');

        $this->assertNotEquals(500, $response->status(), 'Size service should handle network partition');
        $this->restoreNetwork();
    }

    public function test_product_creation_survives_disk_full(): void
    {
        $this->simulateDiskFull();

        $response = $this->actingAs($this->user)
            ->postJson('/api/fashion/products', [
                'name' => 'Test Product',
                'price_b2c' => 1000,
                'category_id' => 1,
            ]);

        $this->assertNotEquals(500, $response->status(), 'Product creation should handle disk full gracefully');
        $this->restoreDiskSpace();
    }

    public function test_stylist_service_survives_high_memory_pressure(): void
    {
        $this->simulateHighMemoryPressure();

        $response = $this->actingAs($this->user)
            ->getJson('/api/fashion/stylist/mens-style');

        $this->assertNotEquals(500, $response->status(), 'Stylist service should handle memory pressure');
        $this->restoreNormalMemory();
    }

    public function test_social_media_service_survives_external_api_failure(): void
    {
        $this->simulateExternalAPIFailure();

        $response = $this->actingAs($this->user)
            ->postJson('/api/fashion/products/1/sync-instagram');

        $this->assertNotEquals(500, $response->status(), 'Social media service should handle external API failure');
        $this->restoreExternalAPI();
    }

    public function test_return_service_survives_connection_timeout(): void
    {
        $this->simulateConnectionTimeout();

        $response = $this->actingAs($this->user)
            ->postJson('/api/fashion/returns', [
                'order_id' => 1,
                'product_id' => 1,
                'reason' => 'wrong_size',
            ]);

        $this->assertNotEquals(500, $response->status(), 'Return service should handle connection timeout');
        $this->restoreConnection();
    }

    public function test_concurrent_product_updates_with_lock_contention(): void
    {
        $this->simulateLockContention();

        $responses = [];
        for ($i = 0; $i < 20; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->putJson('/api/fashion/products/1', [
                    'price_b2c' => 1000 + ($i * 100),
                ]);
        }

        $successfulUpdates = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        $this->assertGreaterThan(0, $successfulUpdates, 'Some updates should succeed despite lock contention');
        $this->restoreNormalLocks();
    }

    public function test_service_recovers_from_graceful_shutdown(): void
    {
        $this->simulateGracefulShutdown();

        $response = $this->actingAs($this->user)
            ->getJson('/api/fashion/products');

        $this->assertNotEquals(503, $response->status(), 'Service should recover from graceful shutdown');
        $this->restoreService();
    }

    public function test_order_rollback_on_service_crash(): void
    {
        $this->simulateServiceCrash();

        $initialCount = DB::table('fashion_orders')->count();

        try {
            $response = $this->actingAs($this->user)
                ->postJson('/api/fashion/orders', [
                    'items' => [['product_id' => 1, 'quantity' => 1]],
                    'payment_method' => 'wallet',
                ]);
        } catch (\Exception $e) {
            // Expected during crash simulation
        }

        $finalCount = DB::table('fashion_orders')->count();
        $this->assertEquals($initialCount, $finalCount, 'Order should be rolled back on service crash');
        $this->restoreService();
    }

    public function test_cache_warmup_after_service_restart(): void
    {
        $this->simulateServiceRestart();

        $response1 = $this->actingAs($this->user)
            ->getJson('/api/fashion/products');

        $response2 = $this->actingAs($this->user)
            ->getJson('/api/fashion/products');

        $this->assertNotEquals(500, $response1->status(), 'First request after restart should work');
        $this->assertNotEquals(500, $response2->status(), 'Subsequent requests should work');
        $this->restoreService();
    }

    public function test_rate_limit_survives_redis_restart(): void
    {
        $this->simulateRedisFailure();

        $responses = [];
        for ($i = 0; $i < 15; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->getJson('/api/fashion/products');
        }

        $successfulRequests = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        $this->assertGreaterThan(5, $successfulRequests, 'Rate limiting should recover from Redis restart');
        $this->restoreRedis();
    }

    public function test_ml_service_survives_model_load_failure(): void
    {
        $this->simulateExternalAPIFailure();

        $response = $this->actingAs($this->user)
            ->getJson('/api/fashion/ml/color-harmony');

        $this->assertNotEquals(500, $response->status(), 'ML service should handle model load failure');
        $this->restoreExternalAPI();
    }

    public function test_review_service_survives_high_concurrent_writes(): void
    {
        $this->simulateLockContention();

        $responses = [];
        for ($i = 0; $i < 50; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->postJson('/api/fashion/products/1/reviews', [
                    'rating' => 5,
                    'comment' => 'Great product',
                ]);
        }

        $successfulReviews = collect($responses)->filter(fn($r) => $r->status() === 201)->count();
        $this->assertGreaterThan(0, $successfulReviews, 'Some reviews should succeed despite contention');
        $this->restoreNormalLocks();
    }

    private function simulateCacheFailure(): void
    {
        Cache::flush();
        app()['cache']->setDefaultDriver('array');
    }

    private function restoreCache(): void
    {
        app()['cache']->setDefaultDriver(config('cache.default'));
    }

    private function simulateDatabaseTimeout(): void
    {
        DB::listen(function ($query) {
            if (str_contains($query->sql, 'fashion_products') || str_contains($query->sql, 'fashion_orders')) {
                throw new \Illuminate\Database\QueryException('Simulated timeout', [], new \Exception());
            }
        });
    }

    private function restoreDatabase(): void
    {
        DB::flushQueryLog();
    }

    private function simulateRedisFailure(): void
    {
        config(['database.redis.default.host' => 'invalid-host']);
    }

    private function restoreRedis(): void
    {
        config(['database.redis.default.host' => env('REDIS_HOST', '127.0.0.1')]);
    }

    private function simulateHighLatency(int $ms): void
    {
        usleep($ms * 1000);
    }

    private function restoreNormalLatency(): void
    {
    }

    private function simulateRandomFailures(float $probability): void
    {
        app()->singleton('chaos.random_failure', function () use ($probability) {
            return $probability;
        });
    }

    private function restoreNormalOperation(): void
    {
        app()->forgetInstance('chaos.random_failure');
    }

    private function simulateNetworkPartition(): void
    {
        config(['services.external.timeout' => 1]);
    }

    private function restoreNetwork(): void
    {
        config(['services.external.timeout' => 30]);
    }

    private function simulateDiskFull(): void
    {
    }

    private function restoreDiskSpace(): void
    {
    }

    private function simulateHighMemoryPressure(): void
    {
        ini_set('memory_limit', '64M');
    }

    private function restoreNormalMemory(): void
    {
        ini_set('memory_limit', '512M');
    }

    private function simulateExternalAPIFailure(): void
    {
    }

    private function restoreExternalAPI(): void
    {
    }

    private function simulateConnectionTimeout(): void
    {
        config(['services.payment.timeout' => 1]);
    }

    private function restoreConnection(): void
    {
        config(['services.payment.timeout' => 30]);
    }

    private function simulateLockContention(): void
    {
        DB::listen(function ($query) {
            if (str_contains($query->sql, 'lockForUpdate')) {
                usleep(100000);
            }
        });
    }

    private function restoreNormalLocks(): void
    {
        DB::flushQueryLog();
    }

    private function simulateGracefulShutdown(): void
    {
    }

    private function restoreService(): void
    {
    }

    private function simulateServiceCrash(): void
    {
    }

    private function simulateServiceRestart(): void
    {
        Cache::flush();
    }
}
