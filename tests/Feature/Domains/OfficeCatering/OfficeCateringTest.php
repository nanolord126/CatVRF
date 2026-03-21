<?php declare(strict_types=1);

namespace Tests\Feature\Domains\OfficeCatering;

use App\Domains\OfficeCatering\Models\OfficeCatering;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class OfficeCateringTest extends TestCase
{
    use DatabaseTransactions;

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_office_catering(): void
    {
        $catering = OfficeCatering::factory()->create();
        $this->assertDatabaseHas('office_caterings', ['sku' => $catering->sku, 'tenant_id' => 1]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function catering_corporate_state(): void
    {
        $catering = OfficeCatering::factory()->corporate()->create();
        $this->assertGreaterThanOrEqual(20, $catering->servings);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function catering_personal_state(): void
    {
        $catering = OfficeCatering::factory()->personal()->create();
        $this->assertEquals(1, $catering->servings);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function catering_meal_type(): void
    {
        $catering = OfficeCatering::factory()->create(['meal_type' => 'lunch']);
        $this->assertEquals('lunch', $catering->meal_type);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function catering_min_order(): void
    {
        $catering = OfficeCatering::factory()->create(['min_order' => 5]);
        $this->assertEquals(5, $catering->min_order);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function catering_price_calculation(): void
    {
        $catering = OfficeCatering::factory()->create(['servings' => 10, 'price_per_serving' => 30000]);
        $this->assertEquals(300000, $catering->total_price);
    }
}
