<?php declare(strict_types=1);

namespace Database\Factories\Domains\Electronics;

use Illuminate\Database\Eloquent\Factories\Factory;

class ElectronicProductFactory extends Factory
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
            'brand' => $this->faker->randomElement(['Samsung', 'Apple', 'Sony', 'LG']),
            'warranty_months' => $this->faker->numberBetween(6, 60),
            'tags' => json_encode(['electronics' => true]),
            'correlation_id' => $this->faker->uuid(),
        ];
    }
}
