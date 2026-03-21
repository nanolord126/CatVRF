<?php declare(strict_types=1);

namespace Tests\Feature\Domains\MeatShops;

use App\Domains\MeatShops\Models\MeatShop;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class MeatShopTest extends TestCase
{
    use DatabaseTransactions;

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_meat_shop(): void
    {
        $meat = MeatShop::factory()->create();
        $this->assertDatabaseHas('meat_shops', ['sku' => $meat->sku, 'tenant_id' => 1]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function meat_premium_state(): void
    {
        $meat = MeatShop::factory()->premium()->create();
        $this->assertGreaterThanOrEqual(250000, $meat->price);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function meat_budget_state(): void
    {
        $meat = MeatShop::factory()->budget()->create();
        $this->assertLessThanOrEqual(150000, $meat->price);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function meat_type(): void
    {
        $meat = MeatShop::factory()->create(['meat_type' => 'beef']);
        $this->assertEquals('beef', $meat->meat_type);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function meat_cut(): void
    {
        $meat = MeatShop::factory()->create(['cut' => 'steak']);
        $this->assertEquals('steak', $meat->cut);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function meat_is_certified(): void
    {
        $meat = MeatShop::factory()->create(['is_certified' => true]);
        $this->assertTrue($meat->is_certified);
    }
}
