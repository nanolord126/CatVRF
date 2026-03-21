<?php declare(strict_types=1);

namespace Database\Factories\Domains\Gifts;

use Illuminate\Database\Eloquent\Factories\Factory;

class GiftProductFactory extends Factory
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
            'stock' => $this->faker->numberBetween(0, 100),
            'occasions' => $this->faker->randomElement(['Birthday', 'Wedding', 'Anniversary', 'Christmas']),
            'gift_wrap_available' => $this->faker->boolean(),
            'in_stock' => $this->faker->boolean(),
            'rating' => $this->faker->randomFloat(2, 1, 5),
            'tags' => json_encode(['gift' => true]),
            'correlation_id' => $this->faker->uuid(),
        ];
    }
}
