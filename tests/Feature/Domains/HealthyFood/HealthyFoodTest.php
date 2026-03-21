<?php declare(strict_types=1);

namespace Tests\Feature\Domains\HealthyFood;

use App\Domains\HealthyFood\Models\HealthyFood;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class HealthyFoodTest extends TestCase
{
    use DatabaseTransactions;

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_healthy_food(): void
    {
        $food = HealthyFood::factory()->create();
        $this->assertDatabaseHas('healthy_foods', ['sku' => $food->sku, 'tenant_id' => 1]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function healthy_food_organic_state(): void
    {
        $food = HealthyFood::factory()->organic()->create();
        $this->assertGreaterThanOrEqual(250000, $food->price);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function healthy_food_budget_state(): void
    {
        $food = HealthyFood::factory()->budget()->create();
        $this->assertLessThanOrEqual(200000, $food->price);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function healthy_food_diet_type(): void
    {
        $food = HealthyFood::factory()->create(['diet_type' => 'keto']);
        $this->assertEquals('keto', $food->diet_type);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function healthy_food_macros(): void
    {
        $food = HealthyFood::factory()->create(['calories' => 500, 'protein_g' => 30, 'carbs_g' => 40, 'fat_g' => 15]);
        $this->assertEquals(500, $food->calories);
        $this->assertEquals(30, $food->protein_g);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function healthy_food_has_rating(): void
    {
        $food = HealthyFood::factory()->create(['rating' => 4.7]);
        $this->assertEquals(4.7, $food->rating);
    }
}
