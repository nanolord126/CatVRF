<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\ConstructionAndRepair\ConstructionAndRepair\ConstructionMaterials\Models\ConstructionMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ConstructionMaterialFactory extends Factory
{
    protected $model = ConstructionMaterial::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'business_group_id' => 1,
            'uuid' => $this->faker->uuid(),
            'correlation_id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'sku' => $this->faker->ean13(),
            'category' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'current_stock' => $this->faker->numberBetween(10, 500),
            'min_stock_threshold' => 10,
            'max_stock_threshold' => 1000,
            'price' => $this->faker->numberBetween(1000, 100000),
            'unit' => 'pc',
            'tags' => json_encode(['construction', 'material']),
        ];
    }
}
