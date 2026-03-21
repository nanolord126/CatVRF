<?php declare(strict_types=1);

namespace Database\Factories\Domains\ConstructionMaterials;

use Illuminate\Database\Eloquent\Factories\Factory;

class ConstructionMaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => $this->faker->uuid(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'sku' => $this->faker->unique()->sku(),
            'price' => $this->faker->numberBetween(1000, 100000),
            'stock' => $this->faker->numberBetween(0, 1000),
            'material_type' => $this->faker->randomElement(['Brick', 'Concrete', 'Paint', 'Wood', 'Steel']),
            'unit' => $this->faker->randomElement(['piece', 'kg', 'm2', 'liter']),
            'consumption_per_m2' => $this->faker->randomFloat(4, 0.1, 10.0),
            'tags' => json_encode(['construction' => true]),
            'correlation_id' => $this->faker->uuid(),
        ];
    }
}
