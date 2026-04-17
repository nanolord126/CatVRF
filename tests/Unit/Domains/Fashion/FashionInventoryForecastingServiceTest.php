<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion;

use App\Domains\Fashion\Services\FashionInventoryForecastingService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\BaseTestCase;

final class FashionInventoryForecastingServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private FashionInventoryForecastingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FashionInventoryForecastingService(
            $this->app->make(AuditService::class),
            $this->app->make(FraudControlService::class),
            $this->app->make('Illuminate\Database\DatabaseManager'),
        );
    }

    public function test_forecast_demand_returns_structure(): void
    {
        $productId = $this->createFashionProduct();

        $result = $this->service->forecastDemand($productId, 30, 'test-123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('product_id', $result);
        $this->assertArrayHasKey('current_stock', $result);
        $this->assertArrayHasKey('forecast', $result);
        $this->assertArrayHasKey('stockout_date', $result);
        $this->assertArrayHasKey('reorder_recommendation', $result);
    }

    public function test_get_reorder_recommendations(): void
    {
        $result = $this->service->getReorderRecommendations('test-123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('products_requiring_reorder', $result);
    }

    public function test_get_out_of_stock_stats(): void
    {
        $result = $this->service->getOutOfStockStats(30, 'test-123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('total_events', $result);
        $this->assertArrayHasKey('total_lost_sales', $result);
    }

    private function createFashionProduct(array $overrides = []): int
    {
        return DB::table('fashion_products')->insertGetId(array_merge([
            'tenant_id' => 1,
            'fashion_store_id' => 1,
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'brand' => 'Test Brand',
            'color' => 'black',
            'price_b2c' => 1000,
            'stock_quantity' => 10,
            'status' => 'active',
            'correlation_id' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
