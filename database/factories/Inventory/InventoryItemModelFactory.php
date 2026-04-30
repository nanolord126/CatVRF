<?php

declare(strict_types=1);

namespace Database\Factories\Inventory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;

final class InventoryItemModelFactory extends Factory
{
    protected $model = InventoryItemModel::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'warehouse_id' => null,
            'business_group_id' => null,
            'name' => fake()->words(3, true),
            'sku' => 'SKU-' . fake()->unique()->numberBetween(1000, 9999),
            'barcode' => fake()->optional()->ean13(),
            'category' => fake()->randomElement(['medication', 'feed', 'grooming_product', 'kitchen_product', 'consumable', 'other']),
            'batch_number' => fake()->optional()->bothify('BATCH-####'),
            'manufacture_date' => fake()->optional()->date(),
            'expiry_date' => fake()->optional()->dateBetween('+30 days', '+2 years'),
            'shelf_life_days' => fake()->optional()->numberBetween(30, 730),
            'quantity' => fake()->numberBetween(0, 100),
            'reserved' => fake()->numberBetween(0, 10),
            'unit' => fake()->randomElement(['шт', 'мл', 'г', 'кг', 'упаковка']),
            'purchase_price' => fake()->randomFloat(2, 10, 1000),
            'selling_price' => fake()->randomFloat(2, 20, 2000),
            'min_stock_level' => fake()->optional()->numberBetween(5, 20),
            'storage_conditions' => fake()->optional()->randomElement(['Холодильник', 'Сухое место', 'Темное место']),
            'storage_location' => fake()->optional()->bothify('Полка #-#'),
            'is_controlled' => fake()->boolean(70), // 70% chance of being controlled
            'status' => 'active',
            'metadata' => null,
            'correlation_id' => null,
        ];
    }

    public function controlled(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_controlled' => true,
            'expiry_date' => fake()->dateBetween('+30 days', '+2 years'),
        ]);
    }

    public function medication(): self
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'medication',
            'is_controlled' => true,
            'unit' => fake()->randomElement(['шт', 'мл']),
        ]);
    }

    public function feed(): self
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'feed',
            'is_controlled' => true,
            'unit' => fake()->randomElement(['кг', 'г', 'упаковка']),
        ]);
    }

    public function expiringSoon(int $days = 30): self
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => now()->addDays(fake()->numberBetween(1, $days)),
            'status' => 'expiring_soon',
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => now()->subDays(fake()->numberBetween(1, 30)),
            'status' => 'expired',
        ]);
    }
}
