<?php declare(strict_types=1);

namespace Tests\Unit\MeatShops;

use Tests\TestCase;
use App\Domains\MeatShops\Models\MeatShop;

final class MeatShopModelTest extends TestCase
{
    public function test_meat_shop_instantiation(): void
    {
        $s = new MeatShop(['name' => 'SteakHouse']);
        $this->assertNotNull($s);
    }
}
