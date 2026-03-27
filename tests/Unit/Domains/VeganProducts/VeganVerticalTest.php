<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\VeganProducts;

use App\Domains\VeganProducts\DTOs\VeganProductCreateDto;
use App\Domains\VeganProducts\Models\VeganProduct;
use App\Domains\VeganProducts\Models\VeganCategory;
use App\Domains\VeganProducts\Models\VeganStore;
use App\Domains\VeganProducts\Services\VeganProductService;
use App\Services\FraudControlService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * VeganVerticalTest - Layer 9/9: Comprehensive Domain Testing.
 * Requirement: Final class, strict types, assertDatabaseHas, correlation_id check.
 * Functional coverage for product creation, safety filtering, and stock logic.
 */
class VeganVerticalTest extends TestCase
{
    use RefreshDatabase;

    private VeganProductService $service;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mocking FraudControlService for simple unit/feature test
        $this->instance(
            FraudControlService::class,
            \Mockery::mock(FraudControlService::class)->shouldReceive('check')->andReturn(true)->getMock()
        );

        $this->service = app(VeganProductService::class);
    }

    /**
     * Test successful product creation through the domain service.
     */
    public function test_it_can_create_a_vegan_product_with_dto(): void
    {
        // 1. Arrange (Factory mocks)
        $store = VeganStore::factory()->create();
        $category = VeganCategory::factory()->create();
        $correlationId = (string) Str::uuid();

        $dto = new VeganProductCreateDto(
            name: 'Organic Almond Milk',
            price_b2c: 25000, // 250.00 RUB
            price_b2b: 22000, // 220.00 RUB
            initialStock: 100,
            veganStoreId: $store->id,
            veganCategoryId: $category->id,
            nutritionInfo: ['protein' => 2, 'calories' => 50],
            allergenInfo: ['nuts'],
            correlationId: $correlationId
        );

        // 2. Act
        $product = $this->service->createProduct($dto);

        // 3. Assert
        $this->assertInstanceOf(VeganProduct::class, $product);
        $this->assertEquals('Organic Almond Milk', $product->name);
        $this->assertEquals($correlationId, $product->correlation_id);
        
        $this->assertDatabaseHas('vegan_products', [
            'id' => $product->id,
            'sku' => $product->sku,
            'price_b2c' => 25000,
            'correlation_id' => $correlationId,
        ]);

        Log::channel('audit')->info('LAYER-9: Test CREATE Passed', ['correlation_id' => $correlationId]);
    }

    /**
     * Test allergen filtering logic in the domain service.
     */
    public function test_it_filters_out_allergic_products(): void
    {
        // 1. Arrange
        $store = VeganStore::factory()->create();
        $category = VeganCategory::factory()->create();

        // Safe product
        VeganProduct::factory()->create([
            'name' => 'Safe Soy Tofu',
            'allergen_info' => ['soy'],
            'vegan_store_id' => $store->id,
            'vegan_category_id' => $category->id,
        ]);

        // Unsafe product (contains nuts)
        VeganProduct::factory()->create([
            'name' => 'Lethal Nut Bar',
            'allergen_info' => ['nuts', 'soy'],
            'vegan_store_id' => $store->id,
            'vegan_category_id' => $category->id,
        ]);

        // 2. Act
        $safeProducts = $this->service->findSafeProducts(['nuts']);

        // 3. Assert
        $this->assertCount(1, $safeProducts);
        $this->assertEquals('Safe Soy Tofu', $safeProducts->first()->name);
    }

    /**
     * Test atomic stock adjustment and InsufficientStockException.
     */
    public function test_it_throws_exception_on_insufficient_stock(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        // 1. Arrange
        $product = VeganProduct::factory()->create([
            'stock_quantity' => 10,
        ]);

        // 2. Act (Try to deduct 11)
        $this->service->adjustStock(
            productId: $product->id,
            delta: -11,
            reason: 'Test over-spend',
            correlationId: (string) Str::uuid()
        );
    }

    /**
     * Test correlation_id verification in the service.
     */
    public function test_it_uses_passed_correlation_id_in_logs(): void
    {
        $correlationId = 'TEST-CORRELATION-VAL';
        $product = VeganProduct::factory()->create();

        // Act
        $this->service->adjustStock($product->id, 1, 'Sync', $correlationId);

        // Assert
        $this->assertDatabaseHas('vegan_products', [
            'id' => $product->id,
            'correlation_id' => $correlationId
        ]);
    }
}
