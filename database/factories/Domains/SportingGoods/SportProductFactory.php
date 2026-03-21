<?php declare(strict_types=1);

namespace Database\Factories\Domains\SportingGoods;

use Illuminate\Database\Eloquent\Factories\Factory;

class SportProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => $this->faker->uuid(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'sku' => $this->faker->unique()->sku(),
            'price' => $this->faker->numberBetween(2000, 100000),
            'stock' => $this->faker->numberBetween(0, 100),
            'sport_type' => $this->faker->randomElement(['Running', 'Swimming', 'Football', 'Basketball', 'Yoga']),
            'brand' => $this->faker->randomElement(['Nike', 'Adidas', 'Puma', 'Reebok']),
            'sizes_available' => json_encode(['XS', 'S', 'M', 'L', 'XL']),
            'tags' => json_encode(['sports' => true]),
            'correlation_id' => $this->faker->uuid(),
        ];
    }
}
