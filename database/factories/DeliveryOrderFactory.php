<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class DeliveryOrderFactory extends Factory
{
    protected $model = \App\Models\Domains\Delivery\DeliveryOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'customer_id' => User::factory(),
            'driver_id' => User::factory(),
            'pickup_address' => fake()->address(),
            'delivery_address' => fake()->address(),
            'distance' => fake()->randomFloat(2, 1, 100),
            'amount' => fake()->randomFloat(2, 50, 500),
            'status' => 'pending',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'assigned',
        ]);
    }
}
