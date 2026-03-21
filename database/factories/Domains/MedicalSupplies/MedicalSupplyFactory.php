<?php declare(strict_types=1);

namespace Database\Factories\Domains\MedicalSupplies;

use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalSupplyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => $this->faker->uuid(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'sku' => $this->faker->unique()->sku(),
            'price' => $this->faker->numberBetween(500, 100000),
            'stock' => $this->faker->numberBetween(0, 500),
            'requires_prescription' => $this->faker->boolean(),
            'active_ingredient' => $this->faker->word(),
            'tags' => json_encode(['medical' => true]),
            'correlation_id' => $this->faker->uuid(),
        ];
    }
}
