<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            "uuid" => (string) Str::uuid(),
            "tenant_id" => 1,
            "sku" => $this->faker->unique()->bothify("SKU-####"),
            "name" => $this->faker->word(),
            "current_stock" => $this->faker->numberBetween(10, 100),
            "hold_stock" => 0,
            "min_stock_threshold" => $this->faker->numberBetween(5, 20),
            "max_stock_threshold" => $this->faker->numberBetween(100, 200),
            "correlation_id" => (string) Str::uuid(),
            "tags" => ["auto-generated" => true],
        ];
    }
}
