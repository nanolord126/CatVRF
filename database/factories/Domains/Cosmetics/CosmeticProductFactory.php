<?php declare(strict_types=1);

namespace Database\Factories\Domains\Cosmetics;

use Illuminate\Database\Eloquent\Factories\Factory;

class CosmeticProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => $this->faker->uuid(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'sku' => $this->faker->unique()->sku(),
            'price' => $this->faker->numberBetween(1000, 50000),
            'stock' => $this->faker->numberBetween(0, 200),
            'brand' => $this->faker->randomElement(['MAC', 'Dior', 'Chanel', 'L\'Oreal']),
            'skin_type' => $this->faker->randomElement(['Oily', 'Dry', 'Combination', 'Sensitive']),
            'ingredients' => json_encode(['natural' => true]),
            'tags' => json_encode(['cosmetics' => true]),
            'correlation_id' => $this->faker->uuid(),
        ];
    }
}
