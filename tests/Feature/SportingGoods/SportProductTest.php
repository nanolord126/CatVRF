<?php

declare(strict_types=1);

namespace Tests\Feature\SportingGoods;

use App\Domains\SportingGoods\Models\SportProduct;
use Database\Factories\SportingGoods\SportProductFactory;
use Tests\TestCase;

final class SportProductTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    public function test_can_create_sport_product(): void
    {
        $product = SportProductFactory::new()->create(['tenant_id' => 1]);
        $this->assertDatabaseHas('sport_products', ['id' => $product->id]);
    }

    public function test_premium_sport_product(): void
    {
        $product = SportProductFactory::new()->premium()->create(['tenant_id' => 1]);
        $this->assertGreaterThanOrEqual(200000, $product->price);
    }

    public function test_budget_sport_product(): void
    {
        $product = SportProductFactory::new()->budget()->create(['tenant_id' => 1]);
        $this->assertLessThanOrEqual(100000, $product->price);
    }

    public function test_sport_type_category(): void
    {
        $product = SportProductFactory::new()->create([
            'tenant_id' => 1,
            'sport_type' => 'football',
        ]);
        $this->assertEquals('football', $product->sport_type);
    }

    public function test_size_range_available(): void
    {
        $product = SportProductFactory::new()->create(['tenant_id' => 1]);
        $this->assertNotNull($product->size_range);
    }

    public function test_stock_available(): void
    {
        $product = SportProductFactory::new()->create([
            'tenant_id' => 1,
            'current_stock' => 50,
        ]);
        $this->assertGreaterThan(0, $product->current_stock);
    }
}
