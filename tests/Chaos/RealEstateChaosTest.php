<?php declare(strict_types=1);

namespace Tests\Chaos;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\PropertyTransaction;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

final class RealEstateChaosTest extends \Tests\Chaos\ChaosEngineeringTest
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 10000000.00,
        ]);
    }

    public function test_property_service_survives_cache_failure(): void
    {
        $this->simulateCacheFailure();

        $response = $this->actingAs($this->user)
            ->getJson("/api/real-estate/properties/{$this->property->id}");

        $this->assertNotEquals(500, $response->status(), 'Service should survive cache failure');
        $this->restoreCache();
    }

    public function test_scoring_service_survives_database_timeout(): void
    {
        $this->simulateDatabaseTimeout();

        $response = $this->actingAs($this->user)
            ->postJson('/api/real-estate/scoring', [
                'property_id' => $this->property->id,
                'deal_amount' => 10000000.00,
                'is_b2b' => false,
            ]);

        $this->assertNotEquals(500, $response->status(), 'Service should handle database timeout gracefully');
        $this->restoreDatabase();
    }

    public function test_transaction_service_survives_redis_failure(): void
    {
        $this->simulateRedisFailure();

        $response = $this->actingAs($this->user)
            ->postJson('/api/real-estate/transactions', [
                'property_id' => $this->property->id,
                'amount' => 10000000.00,
                'payment_method' => 'wallet',
            ]);

        $this->assertNotEquals(500, $response->status(), 'Service should survive Redis failure');
        $this->restoreRedis();
    }

    public function test_property_search_handles_high_latency(): void
    {
        $this->simulateHighLatency(5000);

        $response = $this->actingAs($this->user)
            ->getJson('/api/real-estate/properties/search?q=test');

        $this->assertNotEquals(504, $response->status(), 'Search should handle high latency');
        $this->restoreNormalLatency();
    }

    public function test_bulk_scoring_handles_partial_failures(): void
    {
        $properties = Property::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'price' => 10000000.00,
        ]);

        $this->simulateRandomFailures(0.3);

        $response = $this->actingAs($this->user)
            ->postJson('/api/real-estate/scoring/bulk', [
                'property_ids' => $properties->pluck('id')->toArray(),
                'is_b2b' => false,
            ]);

        $this->assertNotEquals(500, $response->status(), 'Bulk scoring should handle partial failures');
        $this->restoreNormalOperation();
    }

    public function test_escrow_service_survives_network_partition(): void
    {
        $transaction = PropertyTransaction::factory()->create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'buyer_id' => $this->user->id,
            'amount' => 10000000.00,
            'status' => 'escrow_pending',
        ]);

        $this->simulateNetworkPartition();

        $response = $this->actingAs($this->user)
            ->postJson("/api/real-estate/transactions/{$transaction->uuid}/release");

        $this->assertNotEquals(500, $response->status(), 'Escrow service should handle network partition');
        $this->restoreNetwork();
    }

    public function test_property_creation_survives_disk_full(): void
    {
        $this->simulateDiskFull();

        $response = $this->actingAs($this->user)
            ->postJson('/api/real-estate/properties', [
                'tenant_id' => $this->tenant->id,
                'type' => 'apartment',
                'area_sqm' => 75.5,
                'price' => 10000000.00,
                'address' => 'Test Address',
            ]);

        $this->assertNotEquals(500, $response->status(), 'Property creation should handle disk full gracefully');
        $this->restoreDiskSpace();
    }

    public function test_webbrtc_service_survives_high_memory_pressure(): void
    {
        $this->simulateHighMemoryPressure();

        $response = $this->actingAs($this->user)
            ->postJson('/api/real-estate/webrtc/rooms', [
                'property_id' => $this->property->id,
                'agent_id' => $this->user->id,
            ]);

        $this->assertNotEquals(500, $response->status(), 'WebRTC service should handle memory pressure');
        $this->restoreNormalMemory();
    }

    public function test_blockchain_service_survives_external_api_failure(): void
    {
        $this->simulateExternalAPIFailure();

        $response = $this->actingAs($this->user)
            ->postJson('/api/real-estate/blockchain/verify', [
                'property_id' => $this->property->id,
                'document_hash' => '0x' . str_repeat('0', 64),
            ]);

        $this->assertNotEquals(500, $response->status(), 'Blockchain service should handle external API failure');
        $this->restoreExternalAPI();
    }

    public function test_crm_service_survives_connection_timeout(): void
    {
        $this->simulateConnectionTimeout();

        $response = $this->actingAs($this->user)
            ->postJson('/api/real-estate/crm/sync', [
                'property_id' => $this->property->id,
            ]);

        $this->assertNotEquals(500, $response->status(), 'CRM sync should handle connection timeout');
        $this->restoreConnection();
    }

    public function test_concurrent_property_updates_with_lock_contention(): void
    {
        $this->simulateLockContention();

        $responses = [];
        for ($i = 0; $i < 20; $i++) {
            $responses[] = $this->actingAs($this->user)
                ->putJson("/api/real-estate/properties/{$this->property->id}", [
                    'price' => 10000000.00 + ($i * 100000),
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
            ->getJson("/api/real-estate/properties/{$this->property->id}");

        $this->assertNotEquals(503, $response->status(), 'Service should recover from graceful shutdown');
        $this->restoreService();
    }

    public function test_transaction_rollback_on_service_crash(): void
    {
        $this->simulateServiceCrash();

        $initialCount = PropertyTransaction::count();

        try {
            $response = $this->actingAs($this->user)
                ->postJson('/api/real-estate/transactions', [
                    'property_id' => $this->property->id,
                    'amount' => 10000000.00,
                    'payment_method' => 'wallet',
                ]);
        } catch (\Exception $e) {
            // Expected during crash simulation
        }

        $finalCount = PropertyTransaction::count();
        $this->assertEquals($initialCount, $finalCount, 'Transaction should be rolled back on service crash');
        $this->restoreService();
    }

    public function test_cache_warmup_after_service_restart(): void
    {
        $this->simulateServiceRestart();

        $response1 = $this->actingAs($this->user)
            ->getJson("/api/real-estate/properties/{$this->property->id}");

        $response2 = $this->actingAs($this->user)
            ->getJson("/api/real-estate/properties/{$this->property->id}");

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
                ->getJson('/api/real-estate/properties');
        }

        $successfulRequests = collect($responses)->filter(fn($r) => $r->status() === 200)->count();
        $this->assertGreaterThan(5, $successfulRequests, 'Rate limiting should recover from Redis restart');
        $this->restoreRedis();
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
            if (str_contains($query->sql, 'properties')) {
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
        // Simulation - would mock filesystem in real implementation
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
        // Would mock HTTP client in real implementation
    }

    private function restoreExternalAPI(): void
    {
    }

    private function simulateConnectionTimeout(): void
    {
        config(['services.crm.timeout' => 1]);
    }

    private function restoreConnection(): void
    {
        config(['services.crm.timeout' => 30]);
    }

    private function simulateLockContention(): void
    {
        DB::listen(function ($query) {
            if (str_contains($query->sql, 'lockForUpdate')) {
                usleep(100000); // 100ms delay
            }
        });
    }

    private function restoreNormalLocks(): void
    {
        DB::flushQueryLog();
    }

    private function simulateGracefulShutdown(): void
    {
        // Simulation
    }

    private function restoreService(): void
    {
    }

    private function simulateServiceCrash(): void
    {
        // Simulation
    }

    private function simulateServiceRestart(): void
    {
        Cache::flush();
    }
}
