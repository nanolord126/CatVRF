<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion;

use App\Domains\Fashion\Services\FashionSocialMediaTrendService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\BaseTestCase;

final class FashionSocialMediaTrendServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private FashionSocialMediaTrendService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FashionSocialMediaTrendService(
            $this->app->make(AuditService::class),
            $this->app->make(FraudControlService::class),
            $this->app->make('Illuminate\Database\DatabaseManager'),
        );
    }

    public function test_collect_trend_data_returns_structure(): void
    {
        $result = $this->service->collectTrendData('test-123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('platforms', $result);
        $this->assertArrayHasKey('total_trends', $result);
    }

    public function test_analyze_product_trends_creates_record(): void
    {
        $productId = $this->createFashionProduct();
        $result = $this->service->analyzeProductTrends($productId, 'test-123');

        $this->assertArrayHasKey('product_id', $result);
        $this->assertArrayHasKey('trend_score', $result);
        $this->assertArrayHasKey('sentiment', $result);
    }

    public function test_predict_future_trends(): void
    {
        $result = $this->service->predictFutureTrends(7, 'test-123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('predictions', $result);
        $this->assertArrayHasKey('confidence', $result);
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
