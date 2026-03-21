<?php

declare(strict_types=1);

namespace Tests\Feature\Gifts;

use App\Domains\Gifts\Models\GiftProduct;
use Database\Factories\Gifts\GiftProductFactory;
use Tests\TestCase;

final class GiftTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    public function test_can_create_gift_product(): void
    {
        $gift = GiftProductFactory::new()->create(['tenant_id' => 1]);
        $this->assertDatabaseHas('gift_products', ['id' => $gift->id]);
    }

    public function test_luxury_gift(): void
    {
        $gift = GiftProductFactory::new()->luxury()->create(['tenant_id' => 1]);
        $this->assertGreaterThanOrEqual(200000, $gift->price);
    }

    public function test_budget_gift(): void
    {
        $gift = GiftProductFactory::new()->budget()->create(['tenant_id' => 1]);
        $this->assertLessThanOrEqual(100000, $gift->price);
    }

    public function test_gift_wrap_available(): void
    {
        $gift = GiftProductFactory::new()->create(['tenant_id' => 1]);
        $this->assertTrue($gift->gift_wrap_available);
    }

    public function test_gift_by_occasion(): void
    {
        $gift = GiftProductFactory::new()->create([
            'tenant_id' => 1,
            'occasion' => 'birthday',
        ]);
        $this->assertEquals('birthday', $gift->occasion);
    }

    public function test_gift_stock_management(): void
    {
        $gift = GiftProductFactory::new()->create([
            'tenant_id' => 1,
            'current_stock' => 50,
        ]);
        $this->assertGreaterThan(0, $gift->current_stock);
    }
}
