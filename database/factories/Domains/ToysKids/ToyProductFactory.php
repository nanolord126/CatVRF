<?php declare(strict_types=1);

namespace Database\Factories\Domains\ToysKids;

use Illuminate\Database\Eloquent\Factories\Factory;

class ToyProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => $this->faker->uuid(),
            'business_group_id' => $this->faker->uuid(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'sku' => $this->faker->unique()->sku(),
            'price' => $this->faker->numberBetween(500, 50000),
            'stock' => $this->faker->numberBetween(0, 100),
            'age_category' => $this->faker->randomElement(['0-3', '3-6', '6-12', '12+']),
            'tags' => json_encode(['test' => true]),
            'correlation_id' => $this->faker->uuid(),
        ];
    }
}
