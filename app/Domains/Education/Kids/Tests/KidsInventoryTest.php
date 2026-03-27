<?php

declare(strict_types=1);

namespace App\Domains\Education\Kids\Tests;

use App\Domains\Education\Kids\Models\KidsProduct;
use App\Domains\Education\Kids\Services\KidsInventoryService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * KidsInventoryTest - Core logic validation for Layer 3 (Services).
 * Layer: Tests (9/9)
 */
final class KidsInventoryTest extends TestCase
{
    use RefreshDatabase;

    private KidsInventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(KidsInventoryService::class);
    }

    /** @test */
    public function it_successfully_reserves_stock_available_in_inventory()
    {
        $correlationId = (string) Str::uuid();

        // 1. Create a product with 10 stock
        $product = KidsProduct::factory()->create([
            'current_stock' => 10,
            'hold_stock' => 0,
        ]);

        // 2. Reserve 5
        $result = $this->service->reserveProduct(
            productId: $product->id,
            quantity: 5,
            correlationId: $correlationId
        );

        // 3. Assert success and DB update
        $this->assertTrue($result);
        $this->assertDatabaseHas('kids_products', [
            'id' => $product->id,
            'current_stock' => 10,
            'hold_stock' => 5,
        ]);

        // 4. Assert audit logging
        $this->assertLogged('kids_stock_reserved', [
            'product_id' => $product->id,
            'correlation_id' => $correlationId,
        ]);
    }

    /** @test */
    public function it_fails_reservation_for_insufficient_stock()
    {
        $correlationId = (string) Str::uuid();

        $product = KidsProduct::factory()->create([
            'current_stock' => 5,
        ]);

        $result = $this->service->reserveProduct(
            productId: $product->id,
            quantity: 10,
            correlationId: $correlationId
        );

        $this->assertFalse($result);
    }
}
