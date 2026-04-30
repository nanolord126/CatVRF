<?php

declare(strict_types=1);

namespace Modules\Supermarket\Database\Factories;

use Modules\Supermarket\Infrastructure\Models\ReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnItemFactory extends Factory
{
    protected $model = ReturnItem::class;

    public function definition(): array
    {
        return [
            'return_id' => \Modules\Supermarket\Infrastructure\Models\Return::factory(),
            'order_item_id' => \App\Models\OrderItem::factory(),
            'product_id' => \App\Models\Product::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'price_per_unit' => fake()->randomFloat(2, 50, 1000) * 100, // в копейках
            'refund_amount' => fake()->randomFloat(2, 50, 1000) * 100,
            'condition' => fake()->randomElement(['good', 'spoiled', 'damaged']),
        ];
    }

    public function good(): self
    {
        return $this->state(fn (array $attributes) => [
            'condition' => 'good',
        ]);
    }

    public function spoiled(): self
    {
        return $this->state(fn (array $attributes) => [
            'condition' => 'spoiled',
        ]);
    }

    public function damaged(): self
    {
        return $this->state(fn (array $attributes) => [
            'condition' => 'damaged',
        ]);
    }
}
