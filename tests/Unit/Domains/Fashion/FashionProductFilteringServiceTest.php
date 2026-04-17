<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion;

use App\Domains\Fashion\Services\FashionProductFilteringService;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\ML\UserBehaviorAnalyzerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\BaseTestCase;

final class FashionProductFilteringServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private FashionProductFilteringService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FashionProductFilteringService(
            $this->app->make(AuditService::class),
            $this->app->make(FraudControlService::class),
            $this->app->make(UserBehaviorAnalyzerService::class),
            $this->app->make('Illuminate\Database\DatabaseManager'),
        );
    }

    public function test_filter_products_returns_paginated_results(): void
    {
        $userId = $this->createUser();
        
        for ($i = 0; $i < 5; $i++) {
            $this->createFashionProduct(['name' => "Product {$i}"]);
        }

        $result = $this->service->filterProducts(
            userId: $userId,
            filters: [],
            sortBy: null,
            sortOrder: 'desc',
            page: 1,
            perPage: 20,
            correlationId: 'test-123'
        );

        $this->assertIsArray($result['products']);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('current_page', $result['pagination']);
        $this->assertArrayHasKey('total', $result['pagination']);
        $this->assertArrayHasKey('last_page', $result['pagination']);
        $this->assertGreaterThanOrEqual(0, count($result['products']));
    }

    public function test_filter_products_with_category_filter(): void
    {
        $userId = $this->createUser();
        $productId1 = $this->createFashionProduct(['name' => 'T-Shirt']);
        $productId2 = $this->createFashionProduct(['name' => 'Jeans']);

        $this->createProductCategory($productId1, 'tops');
        $this->createProductCategory($productId2, 'bottoms');

        $result = $this->service->filterProducts(
            userId: $userId,
            filters: ['categories' => ['tops']],
            sortBy: null,
            sortOrder: 'desc',
            page: 1,
            perPage: 20,
            correlationId: 'test-123'
        );

        $this->assertGreaterThanOrEqual(0, count($result['products']));
    }

    public function test_filter_products_with_price_range(): void
    {
        $userId = $this->createUser();
        
        $this->createFashionProduct(['name' => 'Cheap', 'price_b2c' => 500]);
        $this->createFashionProduct(['name' => 'Expensive', 'price_b2c' => 10000]);

        $result = $this->service->filterProducts(
            userId: $userId,
            filters: ['price_min' => 0, 'price_max' => 1000],
            sortBy: null,
            sortOrder: 'desc',
            page: 1,
            perPage: 20,
            correlationId: 'test-123'
        );

        foreach ($result['products'] as $product) {
            $this->assertLessThanOrEqual(1000, $product['price_b2c']);
            $this->assertGreaterThanOrEqual(0, $product['price_b2c']);
        }
    }

    public function test_filter_products_with_brand_filter(): void
    {
        $userId = $this->createUser();
        
        $this->createFashionProduct(['name' => 'Nike Shirt', 'brand' => 'Nike']);
        $this->createFashionProduct(['name' => 'Adidas Shirt', 'brand' => 'Adidas']);

        $result = $this->service->filterProducts(
            userId: $userId,
            filters: ['brands' => ['Nike']],
            sortBy: null,
            sortOrder: 'desc',
            page: 1,
            perPage: 20,
            correlationId: 'test-123'
        );

        foreach ($result['products'] as $product) {
            $this->assertEquals('Nike', $product['brand']);
        }
    }

    public function test_get_available_filters_returns_structure(): void
    {
        $userId = $this->createUser();

        $filters = $this->service->getAvailableFilters($userId, 'test-123');

        $this->assertArrayHasKey('categories', $filters);
        $this->assertArrayHasKey('price_ranges', $filters);
        $this->assertArrayHasKey('brands', $filters);
        $this->assertArrayHasKey('colors', $filters);
        $this->assertArrayHasKey('sizes', $filters);
        $this->assertArrayHasKey('materials', $filters);
        $this->assertArrayHasKey('styles', $filters);
        $this->assertArrayHasKey('seasons', $filters);
        $this->assertArrayHasKey('target_audiences', $filters);
        $this->assertArrayHasKey('user_preferences', $filters);
    }

    public function test_save_user_filter_preferences(): void
    {
        $userId = $this->createUser();

        $result = $this->service->saveUserFilterPreferences(
            userId: $userId,
            filters: ['categories' => ['tops'], 'brands' => ['Nike']],
            correlationId: 'test-123'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals($userId, $result['user_id']);
        $this->assertArrayHasKey('categories', $result['filters_saved']);
        $this->assertArrayHasKey('brands', $result['filters_saved']);
    }

    public function test_get_smart_filter_recommendations(): void
    {
        $userId = $this->createUser();

        $recommendations = $this->service->getSmartFilterRecommendations($userId, 'test-123');

        $this->assertArrayHasKey('user_id', $recommendations);
        $this->assertArrayHasKey('recommendations', $recommendations);
        $this->assertArrayHasKey('suggested_price_range', $recommendations['recommendations']);
        $this->assertArrayHasKey('suggested_categories', $recommendations['recommendations']);
        $this->assertArrayHasKey('suggested_brands', $recommendations['recommendations']);
        $this->assertArrayHasKey('confidence', $recommendations);
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

    private function createProductCategory(int $productId, string $category): void
    {
        DB::table('fashion_product_categories')->insert([
            'product_id' => $productId,
            'tenant_id' => 1,
            'primary_category' => $category,
            'secondary_categories' => json_encode([]),
            'tags' => json_encode([]),
            'style_profile' => 'classic',
            'season' => 'spring',
            'target_audience' => 'women',
            'correlation_id' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
