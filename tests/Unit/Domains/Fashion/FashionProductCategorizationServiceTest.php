<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion;

use App\Domains\Fashion\Services\FashionProductCategorizationService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\BaseTestCase;

final class FashionProductCategorizationServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private FashionProductCategorizationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FashionProductCategorizationService(
            $this->app->make(AuditService::class),
            $this->app->make(FraudControlService::class),
            $this->app->make('Illuminate\Database\DatabaseManager'),
        );
    }

    public function test_auto_categorize_product_determines_primary_category(): void
    {
        $productId = $this->createFashionProduct([
            'name' => 'Cotton T-Shirt',
            'description' => 'Comfortable cotton t-shirt',
            'material' => 'cotton',
        ]);

        $result = $this->service->autoCategorizeProduct(
            productId: $productId,
            attributes: [
                'name' => 'Cotton T-Shirt',
                'description' => 'Comfortable cotton t-shirt',
                'material' => 'cotton',
            ],
            correlationId: 'test-123'
        );

        $this->assertEquals('tops', $result['primary_category']);
        $this->assertIsArray($result['secondary_categories']);
        $this->assertIsArray($result['tags']);
        $this->assertNotEmpty($result['style_profile']);
        $this->assertNotEmpty($result['season']);
        $this->assertNotEmpty($result['target_audience']);
    }

    public function test_auto_categorize_product_with_dress(): void
    {
        $productId = $this->createFashionProduct([
            'name' => 'Summer Dress',
            'description' => 'Light summer dress',
        ]);

        $result = $this->service->autoCategorizeProduct(
            productId: $productId,
            attributes: [
                'name' => 'Summer Dress',
                'description' => 'Light summer dress',
            ],
            correlationId: 'test-123'
        );

        $this->assertEquals('dresses', $result['primary_category']);
    }

    public function test_auto_categorize_product_with_shoes(): void
    {
        $productId = $this->createFashionProduct([
            'name' => 'Running Shoes',
            'description' => 'Athletic running shoes',
        ]);

        $result = $this->service->autoCategorizeProduct(
            productId: $productId,
            attributes: [
                'name' => 'Running Shoes',
                'description' => 'Athletic running shoes',
            ],
            correlationId: 'test-123'
        );

        $this->assertEquals('shoes', $result['primary_category']);
    }

    public function test_auto_categorize_product_generates_tags(): void
    {
        $productId = $this->createFashionProduct([
            'name' => 'Nike T-Shirt',
            'description' => 'Sport t-shirt',
            'material' => 'cotton',
            'brand' => 'Nike',
            'price_b2c' => 1500,
        ]);

        $result = $this->service->autoCategorizeProduct(
            productId: $productId,
            attributes: [
                'name' => 'Nike T-Shirt',
                'description' => 'Sport t-shirt',
                'material' => 'cotton',
                'brand' => 'Nike',
                'price_b2c' => 1500,
                'colors' => ['black', 'white'],
            ],
            correlationId: 'test-123'
        );

        $this->assertContains('cotton', $result['tags']);
        $this->assertContains('nike', $result['tags']);
        $this->assertContains('black', $result['tags']);
        $this->assertContains('white', $result['tags']);
        $this->assertContains('mid-range', $result['tags']);
    }

    public function test_bulk_recategorize_products(): void
    {
        $productIds = [
            $this->createFashionProduct(['name' => 'T-Shirt']),
            $this->createFashionProduct(['name' => 'Jeans']),
            $this->createFashionProduct(['name' => 'Dress']),
        ];

        $result = $this->service->bulkRecategorizeProducts(
            productIds: $productIds,
            correlationId: 'test-123'
        );

        $this->assertEquals(3, $result['total_processed']);
        $this->assertEquals(3, $result['successful']);
        $this->assertEquals(0, $result['failed']);
        $this->assertCount(3, $result['results']);
    }

    public function test_get_category_hierarchy_returns_structure(): void
    {
        $hierarchy = $this->service->getCategoryHierarchy();

        $this->assertIsArray($hierarchy);
    }

    public function test_get_smart_category_suggestions_returns_suggestions(): void
    {
        $userId = $this->createUser();
        $productId = $this->createFashionProduct();

        DB::table('product_views')->insert([
            'user_id' => $userId,
            'product_id' => $productId,
            'created_at' => now(),
        ]);

        $suggestions = $this->service->getSmartCategorySuggestions($userId);

        $this->assertIsArray($suggestions['suggested_categories']);
        $this->assertIsArray($suggestions['scores']);
        $this->assertIsArray($suggestions['reasoning']);
        $this->assertArrayHasKey('user_preferences', $suggestions['reasoning']);
        $this->assertArrayHasKey('trending', $suggestions['reasoning']);
        $this->assertArrayHasKey('seasonal', $suggestions['reasoning']);
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

    private function createUser(): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
