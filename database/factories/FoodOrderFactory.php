<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Food\FoodOrder>
 */
final class FoodOrderFactory extends Factory
{
    protected $model = \App\Models\Domains\Food\FoodOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'restaurant_id' => User::factory(),
            'customer_id' => User::factory(),
            'total_amount' => fake()->numberBetween(50000, 500000),
            'status' => 'pending',
            'items' => [
                ['name' => 'Dish 1', 'qty' => 1, 'price' => fake()->numberBetween(10000, 50000)],
            ],
            'delivery_address' => fake()->address(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
