<?php

declare(strict_types=1);

namespace Modules\Analytics\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Modules\Analytics\Application\DTOs\CLVPredictionDTO;
use Modules\Analytics\Application\DTOs\CLVSegmentEnum;
use Modules\Analytics\Application\Facades\SellerAnalytics;
use Modules\Analytics\Application\Services\SellerCLVService;
use Modules\Analytics\Infrastructure\Jobs\CalculateBuyerFeaturesJob;
use Modules\Analytics\Models\BuyerSellerFeatures;
use Tests\TestCase;

/**
 * CLV Integration Tests
 * 
 * Tests the complete CLV prediction system including:
 * - Feature calculation
 * - ML inference
 * - Service methods
 * - Facade API
 * - Caching
 * - Jobs
 */
final class CLVIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;
    private int $sellerId;
    private int $buyerId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantId = 1;
        $this->sellerId = 100;
        $this->buyerId = 200;

        Config::set('analytics.clv.enabled', true);
        Config::set('analytics.clv.cache_ttl', 60);
    }

    public function test_buyer_seller_features_model_creation(): void
    {
        $features = BuyerSellerFeatures::query()->create([
            'tenant_id' => $this->tenantId,
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'r_score' => 5,
            'f_score' => 4,
            'm_score' => 5,
            'recency_days' => 15,
            'frequency_90d' => 5,
            'frequency_180d' => 8,
            'frequency_365d' => 15,
            'monetary_90d' => 15000.00,
            'monetary_180d' => 25000.00,
            'monetary_365d' => 45000.00,
            'avg_order_value' => 3000.00,
            'days_since_first_purchase' => 200,
            'total_orders_all_time' => 15,
            'total_monetary_all_time' => 45000.00,
            'return_rate' => 5.50,
            'review_score' => 4.5,
            'total_reviews' => 3,
            'traffic_search_pct' => 40.00,
            'traffic_recommendation_pct' => 30.00,
            'traffic_direct_pct' => 20.00,
            'traffic_other_pct' => 10.00,
            'last_category' => 'electronics',
            'geo_region' => 'Moscow',
            'geo_city' => 'Moscow',
            'predicted_clv_180d' => 30000.00,
            'predicted_clv_365d' => 60000.00,
            'churn_probability' => 0.15,
            'prediction_confidence' => 0.85,
            'clv_segment' => 'high',
            'model_version' => 'v20260428',
        ]);

        $this->assertDatabaseHas('buyer_seller_features', [
            'tenant_id' => $this->tenantId,
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
        ]);

        $this->assertTrue($features->hasPrediction());
        $this->assertFalse($features->isHighChurnRisk());
        $this->assertFalse($features->isVip());
    }

    public function test_clv_prediction_dto_creation(): void
    {
        $dto = CLVPredictionDTO::fromArray([
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'tenant_id' => $this->tenantId,
            'predicted_clv_180d' => 30000.00,
            'predicted_clv_365d' => 60000.00,
            'churn_probability' => 0.15,
            'confidence' => 0.85,
            'segment' => 'high',
            'model_version' => 'v20260428',
            'predicted_at' => now()->toDateTimeString(),
        ]);

        $this->assertEquals($this->buyerId, $dto->buyerId);
        $this->assertEquals(30000.00, $dto->predictedClv180d);
        $this->assertEquals('high', $dto->segment);
        $this->assertFalse($dto->isHighChurnRisk());
        $this->assertTrue($dto->isHighConfidence());
        $this->assertFalse($dto->isVip());
        $this->assertEquals(5000.0, $dto->getMonthlyClv());
    }

    public function test_clv_segment_enum(): void
    {
        $this->assertEquals('Low', CLVSegmentEnum::LOW->getLabel());
        $this->assertEquals('Medium', CLVSegmentEnum::MEDIUM->getLabel());
        $this->assertEquals('High', CLVSegmentEnum::HIGH->getLabel());
        $this->assertEquals('VIP', CLVSegmentEnum::VIP->getLabel());

        $this->assertEquals('gray', CLVSegmentEnum::LOW->getColor());
        $this->assertEquals('blue', CLVSegmentEnum::MEDIUM->getColor());
        $this->assertEquals('green', CLVSegmentEnum::HIGH->getColor());
        $this->assertEquals('amber', CLVSegmentEnum::VIP->getColor());

        $this->assertEquals(CLVSegmentEnum::LOW, CLVSegmentEnum::fromClv(1000));
        $this->assertEquals(CLVSegmentEnum::MEDIUM, CLVSegmentEnum::fromClv(10000));
        $this->assertEquals(CLVSegmentEnum::HIGH, CLVSegmentEnum::fromClv(30000));
        $this->assertEquals(CLVSegmentEnum::VIP, CLVSegmentEnum::fromClv(60000));
    }

    public function test_seller_clv_service_prediction_with_heuristic_fallback(): void
    {
        // Create feature record without prediction
        BuyerSellerFeatures::query()->create([
            'tenant_id' => $this->tenantId,
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'r_score' => 4,
            'f_score' => 3,
            'm_score' => 4,
            'recency_days' => 45,
            'frequency_90d' => 2,
            'frequency_180d' => 4,
            'frequency_365d' => 8,
            'monetary_90d' => 8000.00,
            'monetary_180d' => 15000.00,
            'monetary_365d' => 30000.00,
            'avg_order_value' => 3750.00,
            'days_since_first_purchase' => 300,
            'total_orders_all_time' => 8,
            'total_monetary_all_time' => 30000.00,
            'return_rate' => 3.00,
            'review_score' => 4.0,
            'total_reviews' => 2,
        ]);

        // Disable ML inference to test heuristic fallback
        Config::set('analytics.clv.ml_deployment_mode', 'http');
        Config::set('analytics.clv.ml_inference_url', 'http://invalid-url:9999');

        $service = app(SellerCLVService::class);
        $prediction = $service->predictForBuyer($this->sellerId, $this->buyerId, $this->tenantId);

        $this->assertInstanceOf(CLVPredictionDTO::class, $prediction);
        $this->assertGreaterThan(0, $prediction->predictedClv180d);
        $this->assertGreaterThan(0, $prediction->churnProbability);
        $this->assertGreaterThan(0, $prediction->confidence);
        $this->assertEquals('heuristic_v1', $prediction->modelVersion);
    }

    public function test_seller_clv_service_caching(): void
    {
        // Create feature record
        BuyerSellerFeatures::query()->create([
            'tenant_id' => $this->tenantId,
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'r_score' => 5,
            'f_score' => 5,
            'm_score' => 5,
            'recency_days' => 5,
            'frequency_90d' => 10,
            'frequency_180d' => 20,
            'frequency_365d' => 40,
            'monetary_90d' => 50000.00,
            'monetary_180d' => 100000.00,
            'monetary_365d' => 200000.00,
            'avg_order_value' => 5000.00,
            'days_since_first_purchase' => 100,
            'total_orders_all_time' => 40,
            'total_monetary_all_time' => 200000.00,
            'return_rate' => 2.00,
            'review_score' => 5.0,
            'total_reviews' => 10,
            'predicted_clv_180d' => 120000.00,
            'predicted_clv_365d' => 240000.00,
            'churn_probability' => 0.05,
            'prediction_confidence' => 0.90,
            'clv_segment' => 'vip',
            'model_version' => 'v20260428',
        ]);

        Config::set('analytics.clv.enabled', true);

        $service = app(SellerCLVService::class);

        // First call - should cache
        $prediction1 = $service->predictForBuyer($this->sellerId, $this->buyerId, $this->tenantId);
        $this->assertEquals('vip', $prediction1->segment);

        // Second call - should return cached result
        $prediction2 = $service->predictForBuyer($this->sellerId, $this->buyerId, $this->tenantId);
        $this->assertEquals($prediction1->predictedClv180d, $prediction2->predictedClv180d);

        // Invalidate cache
        $service->invalidateSellerCache($this->sellerId, $this->tenantId);

        // Third call - should fetch fresh data
        $prediction3 = $service->predictForBuyer($this->sellerId, $this->buyerId, $this->tenantId);
        $this->assertEquals('vip', $prediction3->segment);
    }

    public function test_seller_clv_service_get_top_buyers_by_clv(): void
    {
        // Create multiple feature records
        BuyerSellerFeatures::query()->insert([
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 201,
                'seller_id' => $this->sellerId,
                'r_score' => 5,
                'f_score' => 5,
                'm_score' => 5,
                'recency_days' => 5,
                'frequency_90d' => 10,
                'frequency_180d' => 20,
                'monetary_180d' => 100000.00,
                'predicted_clv_180d' => 120000.00,
                'churn_probability' => 0.05,
                'clv_segment' => 'vip',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 202,
                'seller_id' => $this->sellerId,
                'r_score' => 4,
                'f_score' => 4,
                'm_score' => 4,
                'recency_days' => 30,
                'frequency_90d' => 5,
                'frequency_180d' => 10,
                'monetary_180d' => 30000.00,
                'predicted_clv_180d' => 35000.00,
                'churn_probability' => 0.15,
                'clv_segment' => 'high',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 203,
                'seller_id' => $this->sellerId,
                'r_score' => 3,
                'f_score' => 3,
                'm_score' => 3,
                'recency_days' => 60,
                'frequency_90d' => 2,
                'frequency_180d' => 4,
                'monetary_180d' => 8000.00,
                'predicted_clv_180d' => 10000.00,
                'churn_probability' => 0.30,
                'clv_segment' => 'medium',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = app(SellerCLVService::class);
        $topBuyers = $service->getTopBuyersByCLV($this->sellerId, $this->tenantId, limit: 10);

        $this->assertCount(3, $topBuyers);
        $this->assertEquals(201, $topBuyers[0]['buyer_id']);
        $this->assertEquals(120000.00, $topBuyers[0]['predictedClv180d']);
    }

    public function test_seller_clv_service_get_high_churn_risk_buyers(): void
    {
        // Create feature records with varying churn risk
        BuyerSellerFeatures::query()->insert([
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 301,
                'seller_id' => $this->sellerId,
                'recency_days' => 200,
                'monetary_180d' => 50000.00,
                'predicted_clv_180d' => 60000.00,
                'churn_probability' => 0.80,
                'clv_segment' => 'high',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 302,
                'seller_id' => $this->sellerId,
                'recency_days' => 150,
                'monetary_180d' => 30000.00,
                'predicted_clv_180d' => 35000.00,
                'churn_probability' => 0.60,
                'clv_segment' => 'high',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 303,
                'seller_id' => $this->sellerId,
                'recency_days' => 10,
                'monetary_180d' => 20000.00,
                'predicted_clv_180d' => 25000.00,
                'churn_probability' => 0.20,
                'clv_segment' => 'high',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = app(SellerCLVService::class);
        $atRiskBuyers = $service->getHighChurnRiskBuyers($this->sellerId, $this->tenantId, threshold: 0.5, limit: 10);

        $this->assertCount(2, $atRiskBuyers);
        $this->assertEquals(301, $atRiskBuyers[0]['buyer_id']);
        $this->assertEquals(302, $atRiskBuyers[1]['buyer_id']);
    }

    public function test_seller_clv_service_get_segment_distribution(): void
    {
        // Create feature records across segments
        BuyerSellerFeatures::query()->insert([
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 401,
                'seller_id' => $this->sellerId,
                'predicted_clv_180d' => 2000.00,
                'clv_segment' => 'low',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 402,
                'seller_id' => $this->sellerId,
                'predicted_clv_180d' => 10000.00,
                'clv_segment' => 'medium',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 403,
                'seller_id' => $this->sellerId,
                'predicted_clv_180d' => 30000.00,
                'clv_segment' => 'high',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 404,
                'seller_id' => $this->sellerId,
                'predicted_clv_180d' => 80000.00,
                'clv_segment' => 'vip',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = app(SellerCLVService::class);
        $distribution = $service->getSegmentDistribution($this->sellerId, $this->tenantId);

        $this->assertArrayHasKey('low', $distribution);
        $this->assertArrayHasKey('medium', $distribution);
        $this->assertArrayHasKey('high', $distribution);
        $this->assertArrayHasKey('vip', $distribution);

        $this->assertEquals(1, $distribution['low']['count']);
        $this->assertEquals(1, $distribution['medium']['count']);
        $this->assertEquals(1, $distribution['high']['count']);
        $this->assertEquals(1, $distribution['vip']['count']);
    }

    public function test_seller_clv_service_get_aggregated_metrics(): void
    {
        // Create feature records
        BuyerSellerFeatures::query()->insert([
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 501,
                'seller_id' => $this->sellerId,
                'predicted_clv_180d' => 30000.00,
                'predicted_clv_365d' => 60000.00,
                'churn_probability' => 0.20,
                'clv_segment' => 'high',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 502,
                'seller_id' => $this->sellerId,
                'predicted_clv_180d' => 20000.00,
                'predicted_clv_365d' => 40000.00,
                'churn_probability' => 0.30,
                'clv_segment' => 'high',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 503,
                'seller_id' => $this->sellerId,
                'predicted_clv_180d' => 60000.00,
                'predicted_clv_365d' => 120000.00,
                'churn_probability' => 0.10,
                'clv_segment' => 'vip',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = app(SellerCLVService::class);
        $metrics = $service->getAggregatedCLVMetrics($this->sellerId, $this->tenantId);

        $this->assertEquals(110000.00, $metrics['total_clv_180d']);
        $this->assertEquals(220000.00, $metrics['total_clv_365d']);
        $this->assertEquals(3, $metrics['total_buyers']);
        $this->assertEquals(1, $metrics['vip_count']);
        $this->assertEquals(0, $metrics['high_churn_count']);
        $this->assertEquals(36666.67, $metrics['avg_clv_180d']);
    }

    public function test_facade_api(): void
    {
        // Create feature record
        BuyerSellerFeatures::query()->create([
            'tenant_id' => $this->tenantId,
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'r_score' => 5,
            'f_score' => 5,
            'm_score' => 5,
            'recency_days' => 5,
            'frequency_90d' => 10,
            'frequency_180d' => 20,
            'monetary_180d' => 100000.00,
            'predicted_clv_180d' => 120000.00,
            'predicted_clv_365d' => 240000.00,
            'churn_probability' => 0.05,
            'prediction_confidence' => 0.90,
            'clv_segment' => 'vip',
            'model_version' => 'v20260428',
            'days_since_first_purchase' => 100,
            'total_orders_all_time' => 20,
            'total_monetary_all_time' => 200000.00,
            'avg_order_value' => 10000.00,
            'frequency_365d' => 40,
            'monetary_90d' => 50000.00,
            'monetary_365d' => 200000.00,
            'return_rate' => 2.00,
            'review_score' => 5.0,
            'total_reviews' => 10,
            'traffic_search_pct' => 40.00,
            'traffic_recommendation_pct' => 30.00,
            'traffic_direct_pct' => 20.00,
            'traffic_other_pct' => 10.00,
        ]);

        Config::set('analytics.clv.enabled', true);

        $prediction = SellerAnalytics::forSeller($this->sellerId)
            ->inTenant($this->tenantId)
            ->predictCLV($this->buyerId);

        $this->assertInstanceOf(CLVPredictionDTO::class, $prediction);
        $this->assertEquals('vip', $prediction->segment);
    }

    public function test_calculate_buyer_features_job_dispatch(): void
    {
        Queue::fake();

        CalculateBuyerFeaturesJob::dispatch($this->sellerId, $this->tenantId);

        Queue::assertPushed(CalculateBuyerFeaturesJob::class, function ($job) use ($this->sellerId, $this->tenantId) {
            return $job->sellerId === $this->sellerId && $job->tenantId === $this->tenantId;
        });
    }

    public function test_buyer_seller_features_scopes(): void
    {
        // Create feature records for different sellers
        BuyerSellerFeatures::query()->insert([
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 601,
                'seller_id' => $this->sellerId,
                'predicted_clv_180d' => 50000.00,
                'churn_probability' => 0.30,
                'clv_segment' => 'high',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $this->tenantId,
                'buyer_id' => 602,
                'seller_id' => 999, // Different seller
                'predicted_clv_180d' => 10000.00,
                'churn_probability' => 0.20,
                'clv_segment' => 'medium',
                'model_version' => 'v20260428',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $sellerFeatures = BuyerSellerFeatures::query()
            ->forSeller($this->sellerId)
            ->forTenant($this->tenantId)
            ->get();

        $this->assertCount(1, $sellerFeatures);
        $this->assertEquals(601, $sellerFeatures->first()->buyer_id);

        $vipFeatures = BuyerSellerFeatures::query()
            ->forSegment('vip')
            ->get();

        $this->assertCount(0, $vipFeatures); // No VIP records created

        $highChurnFeatures = BuyerSellerFeatures::query()
            ->highChurnRisk()
            ->get();

        $this->assertCount(0, $highChurnFeatures); // No high churn records created
    }
}
