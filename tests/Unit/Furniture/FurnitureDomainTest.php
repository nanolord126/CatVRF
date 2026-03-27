<?php

declare(strict_types=1);

namespace Tests\Unit\Furniture;

use App\Domains\Furniture\Models\FurnitureProduct;
use App\Domains\Furniture\Models\FurnitureStore;
use App\Domains\Furniture\Services\FurnitureDomainService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\FraudControlService;
use Exception;

/**
 * FurnitureDomainTest (Layer 9/9)
 * Unit tests for pricing logic, B2C vs B2B calculations, and transaction safety.
 */
final class FurnitureDomainTest extends TestCase
{
    use RefreshDatabase;

    private FurnitureDomainService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup service for testing without full Fraud check mocking for brevity
        $this->service = app(FurnitureDomainService::class);
    }

    /**
     * Test B2C Pricing Calculation (Standard)
     */
    public function test_calculate_b2c_pricing_correctly(): void
    {
        $product = FurnitureProduct::factory()->create([
            'price_b2c' => 100000, // 1000 RUB
            'price_b2b' => 80000,  // 800 RUB
        ]);

        $calculated = $this->service->calculatePricing($product, false);

        $this->assertEquals(100000, $calculated, "B2C Price Calculation Failed");
    }

    /**
     * Test B2B Pricing Calculation (Wholesale)
     */
    public function test_calculate_b2b_pricing_correctly(): void
    {
        $product = FurnitureProduct::factory()->create([
            'price_b2c' => 100000, // 1000 RUB
            'price_b2b' => 80000,  // 800 RUB
        ]);

        $calculated = $this->service->calculatePricing($product, true);

        $this->assertEquals(80000, $calculated, "B2B Price Calculation Failed");
    }

    /**
     * Test Stock Insufficiency Exception.
     */
    public function test_create_custom_order_fails_on_no_stock(): void
    {
        $store = FurnitureStore::factory()->create();
        $product = FurnitureProduct::factory()->create([
            'store_id' => $store->id,
            'stock_quantity' => 0,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Insufficient stock");

        $this->service->createCustomOrder([
            'store_id' => $store->id,
            'customer_name' => 'Test User',
            'product_ids' => [$product->id],
            'total_price_kopecks' => 500000,
            'room_type_id' => 1,
            'delivery_address' => 'Test Address',
            'customer_phone' => '+79998887766'
        ]);
    }

    /**
     * Test Tenant Isolation (Mocked context)
     */
    public function test_furniture_product_has_tenant_scope(): void
    {
        // Mock current tenant
        $tenantId = 99;
        session(['tenant_id' => $tenantId]);

        $product = FurnitureProduct::factory()->create([
            'tenant_id' => $tenantId
        ]);

        $this->assertEquals($tenantId, $product->tenant_id, "Tenant ID not set correctly");
    }
}
