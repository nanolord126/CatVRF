<?php

namespace Database\Factories;

use App\Models\Domains\Inventory\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'sku' => $this->faker->unique()->bothify('SKU-####'),
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'cost_price' => $this->faker->numberBetween(10, 1000),
            'selling_price' => $this->faker->numberBetween(20, 2000),
            'quantity' => $this->faker->numberBetween(0, 1000),
            'reorder_level' => $this->faker->numberBetween(5, 50),
            'status' => $this->faker->randomElement(['active', 'inactive', 'discontinued']),
        ];
    }
}
