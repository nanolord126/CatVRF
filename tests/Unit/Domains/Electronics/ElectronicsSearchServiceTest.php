<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics;

use App\Domains\Electronics\DTOs\FilterDto;
use App\Domains\Electronics\DTOs\SearchRequestDto;
use App\Domains\Electronics\DTOs\SearchResponseDto;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Domains\Electronics\Services\ElectronicsSearchService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tests\BaseTestCase;

final class ElectronicsSearchServiceTest extends BaseTestCase
{
    use RefreshDatabase;

    private ElectronicsSearchService $service;
    private FraudControlService|MockObject $fraudService;
    private Cache|MockObject $cache;
    private DatabaseManager|MockObject $db;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraudService = $this->createMock(FraudControlService::class);
        $this->cache = $this->createMock(Cache::class);
        $this->db = $this->createMock(DatabaseManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new ElectronicsSearchService(
            $this->fraudService,
            $this->cache,
            $this->db,
            $this->logger,
        );
    }

    public function test_search_with_basic_query(): void
    {
        // Arrange
        $this->fraudService->expects($this->once())
            ->method('check')
            ->with(
                $this->equalTo(0),
                $this->equalTo('electronics_search'),
                $this->equalTo(0),
                $this->anything(),
            );

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->cache->expects($this->once())
            ->method('put');

        ElectronicsProduct::factory()->count(10)->create([
            'name' => 'iPhone 15 Pro',
            'brand' => 'Apple',
            'category' => 'Smartphones',
            'is_active' => true,
        ]);

        $dto = new SearchRequestDto(
            query: 'iPhone',
            page: 1,
            perPage: 20,
            minPriceKopecks: null,
            maxPriceKopecks: null,
            brands: [],
            categories: [],
            colors: [],
            specsFilters: [],
            inStockOnly: null,
            withDiscount: null,
            sort: ['field' => 'relevance', 'direction' => 'desc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $this->assertInstanceOf(SearchResponseDto::class, $result);
        $this->assertGreaterThan(0, $result->total);
        $this->assertIsArray($result->products);
        $this->assertArrayHasKey('brands', $result->aggregations);
        $this->assertArrayHasKey('categories', $result->aggregations);
    }

    public function test_search_with_brand_filter(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(5)->create([
            'brand' => 'Apple',
            'is_active' => true,
        ]);

        ElectronicsProduct::factory()->count(5)->create([
            'brand' => 'Samsung',
            'is_active' => true,
        ]);

        $this->fraudService->method('check');
        $this->cache->method('get')->willReturn(null);
        $this->cache->method('put');

        $dto = new SearchRequestDto(
            query: '',
            page: 1,
            perPage: 20,
            minPriceKopecks: null,
            maxPriceKopecks: null,
            brands: ['Apple'],
            categories: [],
            colors: [],
            specsFilters: [],
            inStockOnly: null,
            withDiscount: null,
            sort: ['field' => 'relevance', 'direction' => 'desc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $this->assertCount(5, $result->products);
        foreach ($result->products as $product) {
            $this->assertEquals('Apple', $product['brand']);
        }
    }

    public function test_search_with_category_filter(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(5)->create([
            'category' => 'Smartphones',
            'is_active' => true,
        ]);

        ElectronicsProduct::factory()->count(5)->create([
            'category' => 'Laptops',
            'is_active' => true,
        ]);

        $this->fraudService->method('check');
        $this->cache->method('get')->willReturn(null);
        $this->cache->method('put');

        $dto = new SearchRequestDto(
            query: '',
            page: 1,
            perPage: 20,
            minPriceKopecks: null,
            maxPriceKopecks: null,
            brands: [],
            categories: ['Smartphones'],
            colors: [],
            specsFilters: [],
            inStockOnly: null,
            withDiscount: null,
            sort: ['field' => 'relevance', 'direction' => 'desc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $this->assertCount(5, $result->products);
        foreach ($result->products as $product) {
            $this->assertEquals('Smartphones', $product['category']);
        }
    }

    public function test_search_with_price_range(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(5)->create([
            'price_kopecks' => 500000, // 5000 RUB
            'is_active' => true,
        ]);

        ElectronicsProduct::factory()->count(5)->create([
            'price_kopecks' => 5000000, // 50000 RUB
            'is_active' => true,
        ]);

        $this->fraudService->method('check');
        $this->cache->method('get')->willReturn(null);
        $this->cache->method('put');

        $dto = new SearchRequestDto(
            query: '',
            page: 1,
            perPage: 20,
            minPriceKopecks: 100000, // 1000 RUB
            maxPriceKopecks: 1000000, // 10000 RUB
            brands: [],
            categories: [],
            colors: [],
            specsFilters: [],
            inStockOnly: null,
            withDiscount: null,
            sort: ['field' => 'relevance', 'direction' => 'desc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $this->assertCount(5, $result->products);
        foreach ($result->products as $product) {
            $this->assertGreaterThanOrEqual(100000, $product['price_kopecks']);
            $this->assertLessThanOrEqual(1000000, $product['price_kopecks']);
        }
    }

    public function test_search_with_color_filter(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(5)->create([
            'color' => 'Black',
            'is_active' => true,
        ]);

        ElectronicsProduct::factory()->count(5)->create([
            'color' => 'White',
            'is_active' => true,
        ]);

        $this->fraudService->method('check');
        $this->cache->method('get')->willReturn(null);
        $this->cache->method('put');

        $dto = new SearchRequestDto(
            query: '',
            page: 1,
            perPage: 20,
            minPriceKopecks: null,
            maxPriceKopecks: null,
            brands: [],
            categories: [],
            colors: ['Black'],
            specsFilters: [],
            inStockOnly: null,
            withDiscount: null,
            sort: ['field' => 'relevance', 'direction' => 'desc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $this->assertCount(5, $result->products);
        foreach ($result->products as $product) {
            $this->assertEquals('Black', $product['color']);
        }
    }

    public function test_search_with_specs_filter(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(5)->create([
            'specs' => ['ram' => '8GB'],
            'is_active' => true,
        ]);

        ElectronicsProduct::factory()->count(5)->create([
            'specs' => ['ram' => '16GB'],
            'is_active' => true,
        ]);

        $this->fraudService->method('check');
        $this->cache->method('get')->willReturn(null);
        $this->cache->method('put');

        $dto = new SearchRequestDto(
            query: '',
            page: 1,
            perPage: 20,
            minPriceKopecks: null,
            maxPriceKopecks: null,
            brands: [],
            categories: [],
            colors: [],
            specsFilters: ['ram' => ['8GB']],
            inStockOnly: null,
            withDiscount: null,
            sort: ['field' => 'relevance', 'direction' => 'desc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $this->assertCount(5, $result->products);
    }

    public function test_search_with_in_stock_only(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(5)->create([
            'availability_status' => 'in_stock',
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        ElectronicsProduct::factory()->count(5)->create([
            'availability_status' => 'out_of_stock',
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $this->fraudService->method('check');
        $this->cache->method('get')->willReturn(null);
        $this->cache->method('put');

        $dto = new SearchRequestDto(
            query: '',
            page: 1,
            perPage: 20,
            minPriceKopecks: null,
            maxPriceKopecks: null,
            brands: [],
            categories: [],
            colors: [],
            specsFilters: [],
            inStockOnly: true,
            withDiscount: null,
            sort: ['field' => 'relevance', 'direction' => 'desc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $this->assertCount(5, $result->products);
        foreach ($result->products as $product) {
            $this->assertEquals('in_stock', $product['availability_status']);
        }
    }

    public function test_search_with_discount_only(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(5)->create([
            'price_kopecks' => 500000,
            'original_price_kopecks' => 600000,
            'is_active' => true,
        ]);

        ElectronicsProduct::factory()->count(5)->create([
            'price_kopecks' => 500000,
            'original_price_kopecks' => null,
            'is_active' => true,
        ]);

        $this->fraudService->method('check');
        $this->cache->method('get')->willReturn(null);
        $this->cache->method('put');

        $dto = new SearchRequestDto(
            query: '',
            page: 1,
            perPage: 20,
            minPriceKopecks: null,
            maxPriceKopecks: null,
            brands: [],
            categories: [],
            colors: [],
            specsFilters: [],
            inStockOnly: null,
            withDiscount: true,
            sort: ['field' => 'relevance', 'direction' => 'desc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $this->assertCount(5, $result->products);
        foreach ($result->products as $product) {
            $this->assertNotNull($product['original_price_kopecks']);
            $this->assertGreaterThan(0, $product['discount_percentage']);
        }
    }

    public function test_search_with_price_sorting(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(10)->create([
            'price_kopecks' => fn () => rand(100000, 1000000),
            'is_active' => true,
        ]);

        $this->fraudService->method('check');
        $this->cache->method('get')->willReturn(null);
        $this->cache->method('put');

        $dto = new SearchRequestDto(
            query: '',
            page: 1,
            perPage: 20,
            minPriceKopecks: null,
            maxPriceKopecks: null,
            brands: [],
            categories: [],
            colors: [],
            specsFilters: [],
            inStockOnly: null,
            withDiscount: null,
            sort: ['field' => 'price', 'direction' => 'asc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $prices = array_column($result->products, 'price_kopecks');
        $sortedPrices = $prices;
        sort($sortedPrices);
        $this->assertEquals($sortedPrices, $prices);
    }

    public function test_search_with_rating_sorting(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(10)->create([
            'rating' => fn () => rand(30, 50) / 10,
            'is_active' => true,
        ]);

        $this->fraudService->method('check');
        $this->cache->method('get')->willReturn(null);
        $this->cache->method('put');

        $dto = new SearchRequestDto(
            query: '',
            page: 1,
            perPage: 20,
            minPriceKopecks: null,
            maxPriceKopecks: null,
            brands: [],
            categories: [],
            colors: [],
            specsFilters: [],
            inStockOnly: null,
            withDiscount: null,
            sort: ['field' => 'rating', 'direction' => 'desc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $ratings = array_column($result->products, 'rating');
        $sortedRatings = $ratings;
        rsort($sortedRatings);
        $this->assertEquals($sortedRatings, $ratings);
    }

    public function test_search_returns_cached_result(): void
    {
        // Arrange
        $this->fraudService->method('check');
        
        $cachedResponse = [
            'products' => [['id' => 1, 'name' => 'Test']],
            'total' => 1,
            'page' => 1,
            'per_page' => 20,
            'total_pages' => 1,
            'aggregations' => [],
            'metadata' => [],
            'correlation_id' => 'test',
            'search_time_ms' => 100,
        ];

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn($cachedResponse);

        $this->cache->expects($this->never())
            ->method('put');

        $dto = new SearchRequestDto(
            query: 'test',
            page: 1,
            perPage: 20,
            minPriceKopecks: null,
            maxPriceKopecks: null,
            brands: [],
            categories: [],
            colors: [],
            specsFilters: [],
            inStockOnly: null,
            withDiscount: null,
            sort: ['field' => 'relevance', 'direction' => 'desc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $this->assertInstanceOf(SearchResponseDto::class, $result);
        $this->assertEquals(1, $result->total);
    }

    public function test_get_available_filters(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(10)->create([
            'brand' => 'Apple',
            'category' => 'Smartphones',
            'color' => 'Black',
            'is_active' => true,
        ]);

        ElectronicsProduct::factory()->count(5)->create([
            'brand' => 'Samsung',
            'category' => 'Smartphones',
            'color' => 'White',
            'is_active' => true,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        // Act
        $filters = $this->service->getAvailableFilters();

        // Assert
        $this->assertInstanceOf(FilterDto::class, $filters);
        $this->assertArrayHasKey('Apple', $filters->brands);
        $this->assertArrayHasKey('Samsung', $filters->brands);
        $this->assertArrayHasKey('Black', $filters->colors);
        $this->assertArrayHasKey('White', $filters->colors);
        $this->assertArrayHasKey('Smartphones', $filters->categories);
    }

    public function test_get_available_filters_with_category(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(10)->create([
            'category' => 'Smartphones',
            'brand' => 'Apple',
            'is_active' => true,
        ]);

        ElectronicsProduct::factory()->count(10)->create([
            'category' => 'Laptops',
            'brand' => 'Dell',
            'is_active' => true,
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        // Act
        $filters = $this->service->getAvailableFilters('Smartphones');

        // Assert
        $this->assertInstanceOf(FilterDto::class, $filters);
        $this->assertArrayHasKey('Apple', $filters->brands);
        $this->assertArrayNotHasKey('Dell', $filters->brands);
    }

    public function test_get_suggestions(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(5)->create([
            'name' => 'iPhone 15 Pro',
            'brand' => 'Apple',
            'is_active' => true,
            'availability_status' => 'in_stock',
        ]);

        $this->cache->method('remember')->willReturnCallback(function ($key, $ttl, $callback) {
            return $callback();
        });

        // Act
        $suggestions = $this->service->getSuggestions('iPhone', 10);

        // Assert
        $this->assertIsArray($suggestions);
        $this->assertGreaterThan(0, count($suggestions));
        $this->assertArrayHasKey('id', $suggestions[0]);
        $this->assertArrayHasKey('name', $suggestions[0]);
        $this->assertArrayHasKey('brand', $suggestions[0]);
    }

    public function test_get_suggestions_with_short_query(): void
    {
        // Act
        $suggestions = $this->service->getSuggestions('i', 10);

        // Assert
        $this->assertIsArray($suggestions);
        $this->assertEmpty($suggestions);
    }

    public function test_search_pagination(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(50)->create([
            'is_active' => true,
        ]);

        $this->fraudService->method('check');
        $this->cache->method('get')->willReturn(null);
        $this->cache->method('put');

        $dto = new SearchRequestDto(
            query: '',
            page: 2,
            perPage: 10,
            minPriceKopecks: null,
            maxPriceKopecks: null,
            brands: [],
            categories: [],
            colors: [],
            specsFilters: [],
            inStockOnly: null,
            withDiscount: null,
            sort: ['field' => 'relevance', 'direction' => 'desc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $this->assertEquals(2, $result->page);
        $this->assertEquals(10, $result->perPage);
        $this->assertCount(10, $result->products);
        $this->assertEquals(5, $result->totalPages);
    }

    public function test_search_excludes_inactive_products(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(5)->create([
            'is_active' => true,
        ]);

        ElectronicsProduct::factory()->count(5)->create([
            'is_active' => false,
        ]);

        $this->fraudService->method('check');
        $this->cache->method('get')->willReturn(null);
        $this->cache->method('put');

        $dto = new SearchRequestDto(
            query: '',
            page: 1,
            perPage: 20,
            minPriceKopecks: null,
            maxPriceKopecks: null,
            brands: [],
            categories: [],
            colors: [],
            specsFilters: [],
            inStockOnly: null,
            withDiscount: null,
            sort: ['field' => 'relevance', 'direction' => 'desc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $this->assertCount(5, $result->products);
    }

    public function test_search_aggregations_include_price_range(): void
    {
        // Arrange
        ElectronicsProduct::factory()->count(10)->create([
            'price_kopecks' => fn () => rand(100000, 1000000),
            'is_active' => true,
        ]);

        $this->fraudService->method('check');
        $this->cache->method('get')->willReturn(null);
        $this->cache->method('put');

        $dto = new SearchRequestDto(
            query: '',
            page: 1,
            perPage: 20,
            minPriceKopecks: null,
            maxPriceKopecks: null,
            brands: [],
            categories: [],
            colors: [],
            specsFilters: [],
            inStockOnly: null,
            withDiscount: null,
            sort: ['field' => 'relevance', 'direction' => 'desc'],
            correlationId: 'test-correlation',
        );

        // Act
        $result = $this->service->search($dto);

        // Assert
        $this->assertArrayHasKey('price_range', $result->aggregations);
        $this->assertArrayHasKey('min_kopecks', $result->aggregations['price_range']);
        $this->assertArrayHasKey('max_kopecks', $result->aggregations['price_range']);
        $this->assertArrayHasKey('avg_kopecks', $result->aggregations['price_range']);
    }
}
