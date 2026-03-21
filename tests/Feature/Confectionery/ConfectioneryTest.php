<?php

declare(strict_types=1);

namespace Tests\Feature\Confectionery;

use App\Domains\Confectionery\Models\ConfectioneryProduct;
use Database\Factories\Confectionery\ConfectioneryProductFactory;
use Tests\TestCase;

final class ConfectioneryTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    public function test_can_create_product(): void
    {
        $product = ConfectioneryProductFactory::new()->create(['tenant_id' => 1]);
        $this->assertDatabaseHas('confectionery_products', ['id' => $product->id]);
    }

    public function test_luxury_product(): void
    {
        $product = ConfectioneryProductFactory::new()->luxury()->create(['tenant_id' => 1]);
        $this->assertGreaterThanOrEqual(100000, $product->price);
    }

    public function test_budget_product(): void
    {
        $product = ConfectioneryProductFactory::new()->budget()->create(['tenant_id' => 1]);
        $this->assertLessThanOrEqual(80000, $product->price);
    }

    public function test_product_category(): void
    {
        $product = ConfectioneryProductFactory::new()->create(['tenant_id' => 1, 'category' => 'cake']);
        $this->assertEquals('cake', $product->category);
    }

    public function test_shelf_life(): void
    {
        $product = ConfectioneryProductFactory::new()->create(['tenant_id' => 1, 'shelf_life_days' => 14]);
        $this->assertEquals(14, $product->shelf_life_days);
    }

    public function test_product_status(): void
    {
        $product = ConfectioneryProductFactory::new()->create(['tenant_id' => 1, 'status' => 'active']);
        $this->assertEquals('active', $product->status);
    }
}
