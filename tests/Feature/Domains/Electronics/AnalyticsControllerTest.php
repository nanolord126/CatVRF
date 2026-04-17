<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Electronics;

use App\Domains\Electronics\Http\Controllers\AnalyticsController;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Domains\Electronics\Services\ElectronicsAnalyticsService;
use Database\Factories\Electronics\ElectronicsProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BaseTestCase;

final class AnalyticsControllerTest extends BaseTestCase
{
    use RefreshDatabase;

    private AnalyticsController $controller;
    private ElectronicsAnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyticsService = app(ElectronicsAnalyticsService::class);
        $this->controller = new AnalyticsController($this->analyticsService);
    }

    public function test_get_analytics_returns_success_response(): void
    {
        ElectronicsProduct::factory()->count(10)->create([
            'tenant_id' => 0,
            'is_active' => true,
        ]);

        $request = new \Illuminate\Http\Request(['period' => '7d']);
        $response = $this->controller->getAnalytics($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('sales_data', $data);
        $this->assertArrayHasKey('traffic_data', $data);
        $this->assertArrayHasKey('conversion_data', $data);
        $this->assertArrayHasKey('top_products', $data);
        $this->assertArrayHasKey('brand_stats', $data);
        $this->assertArrayHasKey('category_stats', $data);
        $this->assertArrayHasKey('price_distribution', $data);
        $this->assertArrayHasKey('inventory_stats', $data);
        $this->assertArrayHasKey('customer_behavior', $data);
        $this->assertArrayHasKey('period', $data);
    }

    public function test_get_analytics_with_type_filter(): void
    {
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

        $request = new \Illuminate\Http\Request(['period' => '7d', 'type' => 'smartphones']);
        $response = $this->controller->getAnalytics($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('7d', $data['period']);
    }

    public function test_get_analytics_with_invalid_period_uses_default(): void
    {
        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
        ]);

        $request = new \Illuminate\Http\Request(['period' => 'invalid']);
        $response = $this->controller->getAnalytics($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_get_sales_data_returns_correct_structure(): void
    {
        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'price_kopecks' => 500000,
        ]);

        $request = new \Illuminate\Http\Request(['period' => '7d']);
        $response = $this->controller->getSalesData($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('sales_data', $data);
        $this->assertArrayHasKey('total_revenue', $data['sales_data']);
        $this->assertArrayHasKey('total_orders', $data['sales_data']);
        $this->assertArrayHasKey('avg_order_value', $data['sales_data']);
        $this->assertArrayHasKey('revenue_trend', $data['sales_data']);
        $this->assertArrayHasKey('growth_rate', $data['sales_data']);
    }

    public function test_get_traffic_data_returns_correct_structure(): void
    {
        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'views_count' => 100,
        ]);

        $request = new \Illuminate\Http\Request(['period' => '7d']);
        $response = $this->controller->getTrafficData($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('traffic_data', $data);
        $this->assertArrayHasKey('total_views', $data['traffic_data']);
        $this->assertArrayHasKey('unique_visitors', $data['traffic_data']);
        $this->assertArrayHasKey('traffic_trend', $data['traffic_data']);
        $this->assertArrayHasKey('top_sources', $data['traffic_data']);
    }

    public function test_get_conversion_data_returns_correct_structure(): void
    {
        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
        ]);

        $request = new \Illuminate\Http\Request(['period' => '7d']);
        $response = $this->controller->getConversionData($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('conversion_data', $data);
        $this->assertArrayHasKey('conversion_rate', $data['conversion_data']);
        $this->assertArrayHasKey('cart_abandonment_rate', $data['conversion_data']);
        $this->assertArrayHasKey('funnel_stages', $data['conversion_data']);
    }

    public function test_get_top_products_returns_limited_results(): void
    {
        ElectronicsProduct::factory()->count(20)->create([
            'tenant_id' => 0,
            'is_active' => true,
        ]);

        $request = new \Illuminate\Http\Request(['period' => '7d', 'limit' => 5]);
        $response = $this->controller->getTopProducts($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('top_products', $data);
        $this->assertCount(5, $data['top_products']);
    }

    public function test_get_top_products_returns_correct_product_structure(): void
    {
        ElectronicsProduct::factory()->create([
            'tenant_id' => 0,
            'is_active' => true,
            'name' => 'Test Product',
            'brand' => 'TestBrand',
            'price_kopecks' => 500000,
            'rating' => 4.5,
            'reviews_count' => 50,
        ]);

        $request = new \Illuminate\Http\Request(['period' => '7d', 'limit' => 10]);
        $response = $this->controller->getTopProducts($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('top_products', $data);
        
        $firstProduct = $data['top_products'][0];
        $this->assertArrayHasKey('id', $firstProduct);
        $this->assertArrayHasKey('name', $firstProduct);
        $this->assertArrayHasKey('brand', $firstProduct);
        $this->assertArrayHasKey('price', $firstProduct);
        $this->assertArrayHasKey('views', $firstProduct);
        $this->assertArrayHasKey('rating', $firstProduct);
        $this->assertArrayHasKey('reviews', $firstProduct);
    }

    public function test_get_brand_stats_returns_correct_structure(): void
    {
        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'brand' => 'Apple',
            'price_kopecks' => 1000000,
        ]);

        $request = new \Illuminate\Http\Request(['period' => '7d']);
        $response = $this->controller->getBrandStats($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('brand_stats', $data);
        
        $firstBrand = $data['brand_stats'][0];
        $this->assertArrayHasKey('brand', $firstBrand);
        $this->assertArrayHasKey('product_count', $firstBrand);
        $this->assertArrayHasKey('total_revenue', $firstBrand);
        $this->assertArrayHasKey('market_share', $firstBrand);
    }

    public function test_get_category_stats_returns_correct_structure(): void
    {
        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'category' => 'Smartphones',
            'price_kopecks' => 500000,
        ]);

        $request = new \Illuminate\Http\Request(['period' => '7d']);
        $response = $this->controller->getCategoryStats($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('category_stats', $data);
        
        $firstCategory = $data['category_stats'][0];
        $this->assertArrayHasKey('category', $firstCategory);
        $this->assertArrayHasKey('product_count', $firstCategory);
        $this->assertArrayHasKey('total_revenue', $firstCategory);
        $this->assertArrayHasKey('market_share', $firstCategory);
    }

    public function test_get_price_distribution_returns_correct_structure(): void
    {
        ElectronicsProduct::factory()->count(10)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'price_kopecks' => 500000,
        ]);

        $request = new \Illuminate\Http\Request(['period' => '7d']);
        $response = $this->controller->getPriceDistribution($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('price_distribution', $data);
        
        $priceDist = $data['price_distribution'];
        $this->assertArrayHasKey('distribution', $priceDist);
        $this->assertArrayHasKey('avg_price', $priceDist);
        $this->assertArrayHasKey('median_price', $priceDist);
        $this->assertArrayHasKey('min_price', $priceDist);
        $this->assertArrayHasKey('max_price', $priceDist);
    }

    public function test_get_inventory_stats_returns_correct_structure(): void
    {
        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'availability_status' => 'in_stock',
            'stock_quantity' => 10,
            'price_kopecks' => 100000,
        ]);

        $request = new \Illuminate\Http\Request();
        $response = $this->controller->getInventoryStats($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('inventory_stats', $data);
        
        $inventory = $data['inventory_stats'];
        $this->assertArrayHasKey('total_products', $inventory);
        $this->assertArrayHasKey('in_stock', $inventory);
        $this->assertArrayHasKey('low_stock', $inventory);
        $this->assertArrayHasKey('out_of_stock', $inventory);
        $this->assertArrayHasKey('stock_health', $inventory);
        $this->assertArrayHasKey('total_stock_value', $inventory);
    }

    public function test_get_inventory_stats_identifies_low_stock_products(): void
    {
        ElectronicsProduct::factory()->create([
            'tenant_id' => 0,
            'is_active' => true,
            'availability_status' => 'low_stock',
            'stock_quantity' => 2,
            'min_threshold' => 5,
            'name' => 'Low Stock Product',
            'brand' => 'TestBrand',
        ]);

        $request = new \Illuminate\Http\Request();
        $response = $this->controller->getInventoryStats($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertNotEmpty($data['inventory_stats']['low_stock_products']);
    }

    public function test_get_customer_behavior_returns_correct_structure(): void
    {
        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'rating' => 4.5,
            'reviews_count' => 50,
        ]);

        $request = new \Illuminate\Http\Request(['period' => '7d']);
        $response = $this->controller->getCustomerBehavior($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('customer_behavior', $data);
        
        $behavior = $data['customer_behavior'];
        $this->assertArrayHasKey('avg_rating', $behavior);
        $this->assertArrayHasKey('total_reviews', $behavior);
        $this->assertArrayHasKey('repeat_purchase_rate', $behavior);
        $this->assertArrayHasKey('top_referrers', $behavior);
        $this->assertArrayHasKey('device_distribution', $behavior);
        $this->assertArrayHasKey('peak_hours', $behavior);
    }

    public function test_clear_cache_returns_success_response(): void
    {
        $request = new \Illuminate\Http\Request(['type' => 'smartphones']);
        $response = $this->controller->clearCache($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertEquals('smartphones', $data['type']);
    }

    public function test_clear_cache_without_type(): void
    {
        $request = new \Illuminate\Http\Request();
        $response = $this->controller->clearCache($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
    }

    public function test_get_analytics_with_different_periods(): void
    {
        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
        ]);

        $periods = ['1d', '7d', '30d', '90d', '1y'];

        foreach ($periods as $period) {
            $request = new \Illuminate\Http\Request(['period' => $period]);
            $response = $this->controller->getAnalytics($request);

            $this->assertEquals(200, $response->getStatusCode());
            
            $data = json_decode($response->getContent(), true);
            $this->assertEquals($period, $data['period']);
        }
    }

    public function test_get_analytics_with_invalid_type_validation(): void
    {
        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
        ]);

        $request = new \Illuminate\Http\Request(['period' => '7d', 'type' => 'invalid_type']);
        $response = $this->controller->getAnalytics($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_get_top_products_with_invalid_limit_validation(): void
    {
        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
        ]);

        $request = new \Illuminate\Http\Request(['period' => '7d', 'limit' => 100]);
        $response = $this->controller->getTopProducts($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_controller_injects_service_correctly(): void
    {
        $controller = new AnalyticsController($this->analyticsService);
        
        $this->assertInstanceOf(AnalyticsController::class, $controller);
    }

    public function test_all_responses_are_json(): void
    {
        ElectronicsProduct::factory()->count(5)->create([
            'tenant_id' => 0,
            'is_active' => true,
        ]);

        $endpoints = [
            fn () => $this->controller->getAnalytics(new \Illuminate\Http\Request(['period' => '7d'])),
            fn () => $this->controller->getSalesData(new \Illuminate\Http\Request(['period' => '7d'])),
            fn () => $this->controller->getTrafficData(new \Illuminate\Http\Request(['period' => '7d'])),
            fn () => $this->controller->getConversionData(new \Illuminate\Http\Request(['period' => '7d'])),
            fn () => $this->controller->getInventoryStats(new \Illuminate\Http\Request()),
        ];

        foreach ($endpoints as $endpoint) {
            $response = $endpoint();
            $this->assertIsString($response->getContent());
            json_decode($response->getContent());
            $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        }
    }

    public function test_excludes_inactive_products_from_analytics(): void
    {
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

        $request = new \Illuminate\Http\Request(['period' => '7d']);
        $response = $this->controller->getSalesData($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(5, $data['sales_data']['total_orders']);
    }

    public function test_analytics_calculates_correct_metrics(): void
    {
        ElectronicsProduct::factory()->count(10)->create([
            'tenant_id' => 0,
            'is_active' => true,
            'price_kopecks' => 1000000, // 10000 RUB
            'views_count' => 100,
            'rating' => 4.5,
            'reviews_count' => 50,
            'stock_quantity' => 10,
            'availability_status' => 'in_stock',
        ]);

        $request = new \Illuminate\Http\Request(['period' => '7d']);
        $response = $this->controller->getAnalytics($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertEquals(100000, $data['sales_data']['total_revenue']); // 10 * 10000
        $this->assertEquals(10, $data['sales_data']['total_orders']);
        $this->assertEquals(10000, $data['sales_data']['avg_order_value']);
        $this->assertEquals(1000, $data['traffic_data']['total_views']); // 10 * 100
    }
}
