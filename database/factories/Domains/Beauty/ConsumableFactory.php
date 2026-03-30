<?php declare(strict_types=1);

namespace Database\Factories\Domains\Beauty;

use App\Domains\Beauty\Models\Consumable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class ConsumableFactory extends Factory
{
    protected $model = Consumable::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => \App\Models\Tenant::factory(),
            'salon_id' => \App\Domains\Beauty\Models\BeautySalon::factory(),
            'name' => $this->faker->unique()->word . ' ' . $this->faker->word,
            'sku' => $this->faker->unique()->ean8(),
            'unit' => $this->faker->randomElement(['piece', 'ml', 'gram']),
            'current_stock' => $this->faker->numberBetween(100, 1000),
            'min_stock_threshold' => $this->faker->numberBetween(10, 50),
            'price_per_unit_kopeki' => $this->faker->numberBetween(100, 5000), // 1-50 руб
            'correlation_id' => (string) Str::uuid(),
            'tags' => [$this->faker->word, $this->faker->word],
        ];
    }
}
