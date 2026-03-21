<?php declare(strict_types=1);

namespace Database\Factories\Domains\AutoParts;

use Illuminate\Database\Eloquent\Factories\Factory;

class AutoPartItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => $this->faker->uuid(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'sku' => $this->faker->unique()->sku(),
            'price' => $this->faker->numberBetween(5000, 500000),
            'stock' => $this->faker->numberBetween(0, 50),
            'compatible_brand' => $this->faker->randomElement(['Toyota', 'BMW', 'Mercedes', 'Ford']),
            'compatible_model' => $this->faker->word(),
            'part_type' => $this->faker->randomElement(['Engine', 'Transmission', 'Suspension', 'Brake']),
            'tags' => json_encode(['auto_parts' => true]),
            'correlation_id' => $this->faker->uuid(),
        ];
    }
}
