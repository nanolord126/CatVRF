<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics;

use App\Domains\Electronics\DTOs\AnalyticsDto;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Domains\Electronics\Services\ElectronicsAnalyticsService;
use App\Services\FraudControlService;
use Database\Factories\Electronics\ElectronicsProductFactory;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\BaseTestCase;

final class ElectronicsAnalyticsServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private ElectronicsAnalyticsService $service;
    private FraudControlService|MockObject $fraudService;
    private Cache|MockObject $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraudService = $this->createMock(FraudControlService::class);
        $this->cache = $this->createMock(Cache::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $db = $this->app->make(\Illuminate\Database\DatabaseManager::class);

        $this->service = new ElectronicsAnalyticsService(
            $this->fraudService,
            $this->cache,
            $db,
            $logger,
        );
    }

    public function test_get_analytics_performs_fraud_check(): void
    {
        $this->fraudService->expects($this->once())
            ->method('check')
            ->with(
                $this->equalTo(0),
                $this->equalTo('electronics_analytics'),
                $this->equalTo(0),
                $this->callback(function ($metadata) {
                    return isset($metadata['period']) && isset($metadata['type']);
                }),
            );

        ElectronicsProduct::factory()->count(10)->create([
            'tenant_id' => 0,
            'is_active' => true,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $this->service->getAnalytics('7d');
    }

    public function test_get_analytics_returns_analytics_dto(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->count(10)->create([
            'tenant_id' => 0,
            'is_active' => true,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $this->assertInstanceOf(AnalyticsDto::class, $analytics);
        $this->assertIsArray($analytics->salesData);
        $this->assertIsArray($analytics->trafficData);
        $this->assertIsArray($analytics->conversionData);
        $this->assertIsArray($analytics->topProducts);
        $this->assertIsArray($analytics->brandStats);
        $this->assertIsArray($analytics->categoryStats);
        $this->assertIsArray($analytics->priceDistribution);
        $this->assertIsArray($analytics->inventoryStats);
        $this->assertIsArray($analytics->customerBehavior);
    }

    public function test_get_analytics_with_type_filter(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'type' => 'smartphones',
        ]);

        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'type' => 'laptops',
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d', 'smartphones');

        $this->assertInstanceOf(AnalyticsDto::class, $analytics);
        $this->assertEquals('7d', $analytics->period);
    }

    public function test_get_analytics_uses_cache(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
        ]);

        $cachedData = [
            'sales_data' => ['total_revenue' => 100000],
            'traffic_data' => ['total_views' => 500],
            'conversion_data' => ['conversion_rate' => 5.0],
            'top_products' => [],
            'brand_stats' => [],
            'category_stats' => [],
            'price_distribution' => [],
            'inventory_stats' => [],
            'customer_behavior' => [],
            'period' => '7d',
            'correlation_id' => 'test-id',
        ];

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn($cachedData);

        $this->cache->expects($this->never())
            ->method('put');

        $analytics = $this->service->getAnalytics('7d');

        $this->assertEquals(100000, $analytics->salesData['total_revenue']);
    }

    public function test_sales_data_calculates_correct_totals(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->count(10)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'price_kopecks' => 500000, // 5000 RUB
            'views_count' => 100,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $this->assertEquals(50000, $analytics->salesData['total_revenue']); // 10 * 5000
        $this->assertEquals(10, $analytics->salesData['total_orders']);
        $this->assertEquals(5000, $analytics->salesData['avg_order_value']);
    }

    public function test_traffic_data_calculates_correct_metrics(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->count(10)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'views_count' => 100,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $this->assertEquals(1000, $analytics->trafficData['total_views']);
    }

    public function test_top_products_returns_sorted_by_views_and_rating(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->create([
            'tenant_id' => 0,
            'is_active' => true,
            'views_count' => 100,
            'rating' => 4.5,
            'reviews_count' => 50,
            'name' => 'Product A',
        ]);

        ElectronicsProduct::factory()->create([
            'tenant_id' => 0,
            'is_active' => true,
            'views_count' => 500,
            'rating' => 4.8,
            'reviews_count' => 100,
            'name' => 'Product B',
        ]);

        ElectronicsProduct::factory()->create([
            'tenant_id' => 0,
            'is_active' => true,
            'views_count' => 300,
            'rating' => 4.2,
            'reviews_count' => 30,
            'name' => 'Product C',
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $this->assertCount(3, $analytics->topProducts);
        $this->assertEquals('Product B', $analytics->topProducts[0]['name']);
        $this->assertEquals(500, $analytics->topProducts[0]['views']);
    }

    public function test_brand_stats_groups_by_brand(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'brand' => 'Apple',
            'price_kopecks' => 1000000,
        ]);

        ElectronicsProduct::factory()->count(3)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'brand' => 'Samsung',
            'price_kopecks' => 500000,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $this->assertCount(2, $analytics->brandStats);
        
        $appleStats = collect($analytics->brandStats)->firstWhere('brand', 'Apple');
        $this->assertNotNull($appleStats);
        $this->assertEquals(5, $appleStats['product_count']);
        $this->assertEquals(50000, $appleStats['total_revenue']); // 5 * 10000 RUB
    }

    public function test_category_stats_groups_by_category(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'category' => 'Smartphones',
            'price_kopecks' => 500000,
        ]);

        ElectronicsProduct::factory()->count(3)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'category' => 'Laptops',
            'price_kopecks' => 1000000,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $this->assertCount(2, $analytics->categoryStats);
        
        $laptopsStats = collect($analytics->categoryStats)->firstWhere('category', 'Laptops');
        $this->assertNotNull($laptopsStats);
        $this->assertEquals(3, $laptopsStats['product_count']);
    }

    public function test_price_distribution_calculates_correct_ranges(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->create([
            'tenant_id' => 0,
            'is_active' => true,
            'price_kopecks' => 300000, // 3000 RUB
        ]);

        ElectronicsProduct::factory()->create([
            'tenant_id' => 0,
            'is_active' => true,
            'price_kopecks' => 1000000, // 10000 RUB
        ]);

        ElectronicsProduct::factory()->create([
            'tenant_id' => 0,
            'is_active' => true,
            'price_kopecks' => 4000000, // 40000 RUB
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $this->assertArrayHasKey('distribution', $analytics->priceDistribution);
        $this->assertArrayHasKey('avg_price', $analytics->priceDistribution);
        $this->assertArrayHasKey('median_price', $analytics->priceDistribution);
        $this->assertArrayHasKey('min_price', $analytics->priceDistribution);
        $this->assertArrayHasKey('max_price', $analytics->priceDistribution);
    }

    public function test_inventory_stats_calculates_stock_levels(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'availability_status' => 'in_stock',
            'stock_quantity' => 10,
            'price_kopecks' => 100000,
        ]);

        ElectronicsProduct::factory()->count(2)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'availability_status' => 'low_stock',
            'stock_quantity' => 3,
            'price_kopecks' => 100000,
        ]);

        ElectronicsProduct::factory()->count(1)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'availability_status' => 'out_of_stock',
            'stock_quantity' => 0,
            'price_kopecks' => 100000,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $this->assertEquals(8, $analytics->inventoryStats['total_products']);
        $this->assertEquals(5, $analytics->inventoryStats['in_stock']);
        $this->assertEquals(2, $analytics->inventoryStats['low_stock']);
        $this->assertEquals(1, $analytics->inventoryStats['out_of_stock']);
    }

    public function test_inventory_stats_identifies_low_stock_products(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->create([
            'tenant_id' => 0,
            'is_active' => true,
            'availability_status' => 'low_stock',
            'stock_quantity' => 2,
            'min_threshold' => 5,
            'name' => 'Low Stock Product',
            'brand' => 'TestBrand',
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $this->assertNotEmpty($analytics->inventoryStats['low_stock_products']);
        $this->assertEquals('Low Stock Product', $analytics->inventoryStats['low_stock_products'][0]['name']);
    }

    public function test_customer_behavior_includes_required_metrics(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'rating' => 4.5,
            'reviews_count' => 50,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $this->assertArrayHasKey('avg_rating', $analytics->customerBehavior);
        $this->assertArrayHasKey('total_reviews', $analytics->customerBehavior);
        $this->assertArrayHasKey('repeat_purchase_rate', $analytics->customerBehavior);
        $this->assertArrayHasKey('top_referrers', $analytics->customerBehavior);
        $this->assertArrayHasKey('device_distribution', $analytics->customerBehavior);
        $this->assertArrayHasKey('peak_hours', $analytics->customerBehavior);
    }

    public function test_clear_cache_clears_specific_type_cache(): void
    {
        $this->cache->expects($this->once())
            ->method('forget')
            ->with('electronics_analytics_0_7d_smartphones');

        $this->service->clearCache('smartphones');
    }

    public function test_clear_cache_clears_all_periods(): void
    {
        $this->cache->expects($this->exactly(5))
            ->method('forget');

        $this->service->clearCache();
    }

    public function test_get_date_range_returns_correct_dates(): void
    {
        $this->fraudService->method('check');

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $this->assertEquals('7d', $analytics->period);
    }

    public function test_conversion_rate_calculation(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->count(10)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'views_count' => 100,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $this->assertArrayHasKey('conversion_rate', $analytics->conversionData);
        $this->assertIsNumeric($analytics->conversionData['conversion_rate']);
    }

    public function test_market_share_calculation_in_brand_stats(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'brand' => 'Apple',
            'price_kopecks' => 1000000,
        ]);

        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'brand' => 'Samsung',
            'price_kopecks' => 1000000,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $totalMarketShare = collect($analytics->brandStats)->sum('market_share');
        $this->assertEquals(100, $totalMarketShare);
    }

    public function test_analytics_dto_to_array(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');
        $array = $analytics->toArray();

        $this->assertArrayHasKey('sales_data', $array);
        $this->assertArrayHasKey('traffic_data', $array);
        $this->assertArrayHasKey('conversion_data', $array);
        $this->assertArrayHasKey('top_products', $array);
        $this->assertArrayHasKey('brand_stats', $array);
        $this->assertArrayHasKey('category_stats', $array);
        $this->assertArrayHasKey('price_distribution', $array);
        $this->assertArrayHasKey('inventory_stats', $array);
        $this->assertArrayHasKey('customer_behavior', $array);
        $this->assertArrayHasKey('period', $array);
        $this->assertArrayHasKey('correlation_id', $array);
    }

    public function test_analytics_dto_from_array(): void
    {
        $data = [
            'sales_data' => [],
            'traffic_data' => [],
            'conversion_data' => [],
            'top_products' => [],
            'brand_stats' => [],
            'category_stats' => [],
            'price_distribution' => [],
            'inventory_stats' => [],
            'customer_behavior' => [],
            'period' => '7d',
            'correlation_id' => 'test-id',
        ];

        $dto = AnalyticsDto::fromArray($data);

        $this->assertEquals('7d', $dto->period);
        $this->assertEquals('test-id', $dto->correlationId);
    }

    public function test_excludes_inactive_products_from_analytics(): void
    {
        $this->fraudService->method('check');

        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'price_kopecks' => 100000,
        ]);

        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => false,
            'price_kopecks' => 100000,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        $analytics = $this->service->getAnalytics('7d');

        $this->assertEquals(5, $analytics->salesData['total_orders']);
    }
}
