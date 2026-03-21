<?php declare(strict_types=1);

namespace Database\Factories\Domains\Jewelry;

use Illuminate\Database\Eloquent\Factories\Factory;

class JewelryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => $this->faker->uuid(),
            'name' => $this->faker->sentence(2),
            'description' => $this->faker->paragraph(),
            'sku' => $this->faker->unique()->sku(),
            'price' => $this->faker->numberBetween(50000, 5000000),
            'stock' => $this->faker->numberBetween(0, 20),
            'metal' => $this->faker->randomElement(['Gold', 'Silver', 'Platinum']),
            'stone' => $this->faker->randomElement(['Diamond', 'Ruby', 'Sapphire', 'Emerald']),
            'certificate_code' => $this->faker->unique()->sku(),
            'tags' => json_encode(['jewelry' => true]),
            'correlation_id' => $this->faker->uuid(),
        ];
    }
}
