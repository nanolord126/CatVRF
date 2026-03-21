<?php declare(strict_types=1);

namespace Database\Factories\Domains\Furniture;

use Illuminate\Database\Eloquent\Factories\Factory;

class FurnitureItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => $this->faker->uuid(),
            'name' => $this->faker->sentence(2),
            'description' => $this->faker->paragraph(),
            'sku' => $this->faker->unique()->sku(),
            'price' => $this->faker->numberBetween(10000, 500000),
            'stock' => $this->faker->numberBetween(0, 30),
            'style' => $this->faker->randomElement(['modern', 'classic', 'minimalist', 'vintage']),
            'material' => $this->faker->randomElement(['wood', 'leather', 'fabric', 'metal']),
            'dimensions' => json_encode(['length' => 200, 'width' => 100, 'height' => 80]),
            'tags' => json_encode(['furniture' => true]),
            'correlation_id' => $this->faker->uuid(),
        ];
    }
}
