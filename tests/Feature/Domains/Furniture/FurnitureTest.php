<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Furniture;

use App\Domains\Furniture\Models\Furniture;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class FurnitureTest extends TestCase
{
    use DatabaseTransactions;

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_furniture(): void
    {
        $furniture = Furniture::factory()->create();
        $this->assertDatabaseHas('furnitures', ['sku' => $furniture->sku, 'tenant_id' => 1]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function furniture_luxury_state(): void
    {
        $furniture = Furniture::factory()->luxury()->create();
        $this->assertGreaterThanOrEqual(250000, $furniture->price);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function furniture_budget_state(): void
    {
        $furniture = Furniture::factory()->budget()->create();
        $this->assertLessThanOrEqual(150000, $furniture->price);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function furniture_category(): void
    {
        $furniture = Furniture::factory()->create(['category' => 'sofa']);
        $this->assertEquals('sofa', $furniture->category);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function furniture_material(): void
    {
        $furniture = Furniture::factory()->create(['material' => 'leather']);
        $this->assertEquals('leather', $furniture->material);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function furniture_has_rating(): void
    {
        $furniture = Furniture::factory()->create(['rating' => 4.5]);
        $this->assertEquals(4.5, $furniture->rating);
    }
}
