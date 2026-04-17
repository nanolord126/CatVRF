<?php declare(strict_types=1);

namespace Tests\Unit\Services\ML;

use Tests\TestCase;
use App\Services\ML\FraudMLFeatureStore;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class FraudMLFeatureStoreTest extends TestCase
{
    use RefreshDatabase;

    private FraudMLFeatureStore $featureStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->featureStore = app(FraudMLFeatureStore::class);
        Redis::flushdb();
    }

    public function test_store_features_saves_to_redis(): void
    {
        $features = ['amount_log' => 5.5, 'hour_of_day' => 14];
        
        $this->featureStore->storeFeatures(
            'user',
            '123',
            $features,
            'test-correlation'
        );

        $retrieved = $this->featureStore->getFeatures('user', '123');
        
        $this->assertNotNull($retrieved);
        $this->assertEquals($features, $retrieved['features']);
        $this->assertArrayHasKey('timestamp', $retrieved);
        $this->assertArrayHasKey('version', $retrieved);
    }

    public function test_get_features_returns_null_when_not_found(): void
    {
        $features = $this->featureStore->getFeatures('user', 'nonexistent');
        
        $this->assertNull($features);
    }

    public function test_get_or_compute_features_computes_when_missing(): void
    {
        $computedFeatures = ['amount_log' => 3.2, 'hour_of_day' => 10];
        
        $features = $this->featureStore->getOrComputeFeatures(
            'user',
            '456',
            fn() => $computedFeatures,
            'test-correlation'
        );

        $this->assertEquals($computedFeatures, $features);
        
        // Second call should retrieve from cache
        $features2 = $this->featureStore->getOrComputeFeatures(
            'user',
            '456',
            fn() => ['should_not_be_called' => true],
            'test-correlation'
        );
        
        $this->assertEquals($computedFeatures, $features2);
    }

    public function test_invalidate_features_removes_from_redis(): void
    {
        $features = ['amount_log' => 4.5];
        
        $this->featureStore->storeFeatures('user', '789', $features);
        
        $this->assertNotNull($this->featureStore->getFeatures('user', '789'));
        
        $this->featureStore->invalidateFeatures('user', '789');
        
        $this->assertNull($this->featureStore->getFeatures('user', '789'));
    }

    public function test_batch_store_features_processes_multiple(): void
    {
        $batch = [
            [
                'entity_type' => 'user',
                'entity_id' => '1',
                'features' => ['amount_log' => 1.0],
                'correlation_id' => 'test1',
            ],
            [
                'entity_type' => 'user',
                'entity_id' => '2',
                'features' => ['amount_log' => 2.0],
                'correlation_id' => 'test2',
            ],
        ];

        $this->featureStore->batchStoreFeatures($batch);

        $this->assertNotNull($this->featureStore->getFeatures('user', '1'));
        $this->assertNotNull($this->featureStore->getFeatures('user', '2'));
    }

    public function test_extract_and_store_operation_features(): void
    {
        $features = $this->featureStore->extractAndStoreOperationFeatures(
            tenantId: 1,
            userId: 100,
            operationType: 'payment',
            amount: 1000.0,
            context: [
                'tenant_risk_profile' => 'low',
                'account_age_days' => 365,
            ],
            correlationId: 'test-op-123'
        );

        $this->assertIsArray($features);
        $this->assertArrayHasKey('amount_log', $features);
        $this->assertArrayHasKey('tenant_id', $features);
        $this->assertArrayHasKey('user_id', $features);
        $this->assertEquals('payment', $features['operation_type']);
        
        // Verify stored in multiple places
        $this->assertNotNull($this->featureStore->getFeatures('user', '100'));
        $this->assertNotNull($this->featureStore->getFeatures('tenant', '1'));
        $this->assertNotNull($this->featureStore->getFeatures('operation', 'test-op-123'));
    }

    public function test_get_feature_stats_returns_metrics(): void
    {
        $this->featureStore->storeFeatures('user', '1', ['test' => 1]);
        $this->featureStore->storeFeatures('user', '2', ['test' => 2]);

        $stats = $this->featureStore->getFeatureStats();

        $this->assertArrayHasKey('total_features_stored', $stats);
        $this->assertArrayHasKey('redis_memory_usage', $stats);
        $this->assertGreaterThan(0, $stats['total_features_stored']);
    }
}
