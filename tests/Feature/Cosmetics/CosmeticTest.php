<?php

declare(strict_types=1);

namespace Tests\Feature\Cosmetics;

use App\Domains\Cosmetics\Models\CosmeticProduct;
use App\Domains\Cosmetics\Models\CosmeticOrder;
use Database\Factories\Cosmetics\CosmeticProductFactory;
use Database\Factories\Cosmetics\CosmeticOrderFactory;
use Tests\TestCase;

final class CosmeticTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    public function test_can_create_cosmetic_product(): void
    {
        $product = CosmeticProductFactory::new()->create([
            'tenant_id' => 1,
            'name' => 'Test Lipstick',
        ]);

        $this->assertDatabaseHas('cosmetic_products', [
            'id' => $product->id,
            'name' => 'Test Lipstick',
        ]);
    }

    public function test_can_create_cosmetic_order(): void
    {
        $product = CosmeticProductFactory::new()->create(['tenant_id' => 1]);
        $order = CosmeticOrderFactory::new()->create([
            'tenant_id' => 1,
            'user_id' => 1,
        ]);

        $this->assertDatabaseHas('cosmetic_orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_luxury_cosmetic_product(): void
    {
        $product = CosmeticProductFactory::new()->luxury()->create(['tenant_id' => 1]);

        $this->assertGreaterThanOrEqual(200000, $product->price);
        $this->assertTrue($product->cruelty_free);
    }

    public function test_drugstore_cosmetic_product(): void
    {
        $product = CosmeticProductFactory::new()->drugstore()->create(['tenant_id' => 1]);

        $this->assertLessThanOrEqual(80000, $product->price);
    }

    public function test_order_status_transitions(): void
    {
        $order = CosmeticOrderFactory::new()->pending()->create(['tenant_id' => 1]);
        
        $this->assertEquals('pending', $order->status);
        
        $order->update(['status' => 'delivered']);
        
        $this->assertEquals('delivered', $order->status);
    }

    public function test_product_stock_check(): void
    {
        $product = CosmeticProductFactory::new()->create([
            'tenant_id' => 1,
            'current_stock' => 100,
            'min_stock_threshold' => 50,
        ]);

        $this->assertGreaterThan($product->min_stock_threshold, $product->current_stock);
    }
}
