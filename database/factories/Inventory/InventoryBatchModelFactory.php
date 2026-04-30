<?php

declare(strict_types=1);

namespace Database\Factories\Inventory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Infrastructure\Models\InventoryBatchModel;

final class InventoryBatchModelFactory extends Factory
{
    protected $model = InventoryBatchModel::class;

    public function definition(): array
    {
        return [
            'inventory_item_id' => null, // Must be set when creating
            'tenant_id' => 1,
            'batch_number' => 'BATCH-' . fake()->unique()->numberBetween(1000, 9999),
            'manufacture_date' => fake()->dateBetween('-1 year', '-30 days'),
            'expiry_date' => fake()->dateBetween('+30 days', '+2 years'),
            'initial_quantity' => fake()->numberBetween(10, 100),
            'current_quantity' => fn (array $attributes) => $attributes['initial_quantity'],
            'purchase_price' => fake()->randomFloat(2, 10, 1000),
            'storage_location' => fake()->optional()->bothify('Холодильник #, Полка #'),
            'status' => 'active',
            'metadata' => null,
            'correlation_id' => null,
        ];
    }

    public function forItem(int $itemId): self
    {
        return $this->state(fn (array $attributes) => [
            'inventory_item_id' => $itemId,
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

    public function partiallyDepleted(): self
    {
        return $this->state(fn (array $attributes) => [
            'current_quantity' => (int) ($attributes['initial_quantity'] * fake()->randomFloat(2, 0.1, 0.8)),
        ]);
    }
}
