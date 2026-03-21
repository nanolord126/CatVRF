<?php

declare(strict_types=1);

namespace Tests\Feature\Jewelry;

use App\Domains\Jewelry\Models\JewelryItem;
use App\Domains\Jewelry\Models\JewelryOrder;
use Database\Factories\Jewelry\JewelryItemFactory;
use Database\Factories\Jewelry\JewelryOrderFactory;
use Tests\TestCase;

final class JewelryTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    public function test_can_create_jewelry_item(): void
    {
        $item = JewelryItemFactory::new()->create([
            'tenant_id' => 1,
            'name' => 'Test Ring',
        ]);

        $this->assertDatabaseHas('jewelry_items', [
            'id' => $item->id,
            'name' => 'Test Ring',
        ]);
    }

    public function test_can_create_jewelry_order(): void
    {
        $item = JewelryItemFactory::new()->create(['tenant_id' => 1]);
        $order = JewelryOrderFactory::new()->create([
            'tenant_id' => 1,
            'user_id' => 1,
            'item_id' => $item->id,
        ]);

        $this->assertDatabaseHas('jewelry_orders', [
            'id' => $order->id,
            'item_id' => $item->id,
        ]);
    }

    public function test_luxury_jewelry_item(): void
    {
        $item = JewelryItemFactory::new()->luxury()->create(['tenant_id' => 1]);

        $this->assertEquals('platinum', $item->metal);
        $this->assertEquals('950', $item->purity);
        $this->assertTrue($item->certificate_required);
    }

    public function test_affordable_jewelry_item(): void
    {
        $item = JewelryItemFactory::new()->affordable()->create(['tenant_id' => 1]);

        $this->assertEquals('silver', $item->metal);
        $this->assertFalse($item->certificate_required);
    }

    public function test_jewelry_weight_calculation(): void
    {
        $item = JewelryItemFactory::new()->create([
            'tenant_id' => 1,
            'weight_grams' => 5.5,
        ]);

        $this->assertEquals(5.5, $item->weight_grams);
    }

    public function test_order_status_flow(): void
    {
        $order = JewelryOrderFactory::new()->pending()->create(['tenant_id' => 1]);
        
        $this->assertEquals('pending', $order->status);
        
        $order->update(['status' => 'shipped', 'payment_status' => 'paid']);
        
        $this->assertEquals('shipped', $order->status);
        $this->assertEquals('paid', $order->payment_status);
    }
}
