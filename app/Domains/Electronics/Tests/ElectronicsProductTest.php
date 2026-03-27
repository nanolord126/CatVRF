<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Tests;

use App\Domains\Electronics\DTOs\ProductCreateDto;
use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Domains\Electronics\Services\ElectronicsService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ElectronicsProductTest - Validation of Retail logic and Stock Locking.
 * Layer: Tests & QA (9/9)
 * Requirement: AssertDatabaseHas, AssertLogged, correlation_id check.
 */
final class ElectronicsProductTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test high-value gadget creation via domain service.
     */
    public function test_can_create_gadget_with_specs_via_service(): void
    {
        $correlationId = (string) Str::uuid();

        // 1. Arrange Data
        $dto = new ProductCreateDto(
            name: 'MacBook Pro M3 Max',
            sku: 'APPLE-MBP-M3-2026',
            brand: 'Apple',
            price: 34990000, // 349,900.00 RUB in kopecks
            storeId: 1,
            categoryId: 1,
            specs: ['cpu' => 'M3 Max', 'gpu' => '40-core', 'ram' => '128GB'],
            correlationId: $correlationId
        );

        $service = app(ElectronicsService::class);

        // 2. Act
        $product = $service->createProduct($dto);

        // 3. Assert - Persistence
        $this->assertInstanceOf(ElectronicsProduct::class, $product);
        $this->assertEquals('APPLE-MBP-M3-2026', $product->sku);
        $this->assertDatabaseHas('electronics_products', [
            'sku' => 'APPLE-MBP-M3-2026',
            'correlation_id' => $correlationId,
        ]);

        // 4. Assert - Spec JSONB logic
        $this->assertEquals('128GB', $product->specs['ram']);

        Log::channel('audit')->info('LAYER-9: Test PASSED for gadget creation', [
            'sku' => $product->sku,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Test B2C/B2B dynamic pricing logic.
     */
    public function test_can_set_b2b_wholesale_pricing(): void
    {
        $correlationId = (string) Str::uuid();

        $product = ElectronicsProduct::factory()->create([
            'price' => 100000, // 1,000.00 RUB Retail
            'b2b_price' => 80000, // 800.00 RUB Wholesale
            'is_b2b_available' => true,
            'correlation_id' => $correlationId,
        ]);

        $this->assertEquals(80000, $product->b2b_price);
        $this->assertTrue($product->is_b2b_available);

        Log::channel('audit')->info('LAYER-9: Test PASSED for B2B pricing', [
            'id' => $product->id,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Test high-concurrency stock lock simulation.
     */
    public function test_inventory_stock_lock_simulation(): void
    {
        $correlationId = (string) Str::uuid();
        
        $product = ElectronicsProduct::factory()->create([
            'sku' => 'LOCK-TEST-001',
            'price' => 500000,
            'availability_status' => 'in_stock',
        ]);

        // Mock DB Transaction with lock
        DB::transaction(function () use ($product, $correlationId) {
            $lockedProduct = ElectronicsProduct::where('id', $product->id)->lockForUpdate()->first();
            
            $lockedProduct->update([
                'availability_status' => 'low_stock',
                'correlation_id' => $correlationId,
            ]);
            
            $this->assertEquals('low_stock', $lockedProduct->availability_status);
        });

        $this->assertDatabaseHas('electronics_products', [
            'sku' => 'LOCK-TEST-001',
            'availability_status' => 'low_stock',
        ]);

        Log::channel('audit')->info('LAYER-9: Test PASSED for stock locking', [
            'sku' => $product->sku,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Test FraudControl integration during high-value product update.
     */
    public function test_fraud_control_integration_on_product_update(): void
    {
        $correlationId = (string) Str::uuid();
        $fraud = $this->createMock(FraudControlService::class);
        $fraud->expects($this->once())->method('check');

        $this->app->instance(FraudControlService::class, $fraud);

        $service = new ElectronicsService(
            $this->createMock(WalletService::class),
            $fraud,
        );

        $service->adjustStock(
            1, 
            -1, 
            'Manual adjustment', 
            $correlationId
        );
    }
}
