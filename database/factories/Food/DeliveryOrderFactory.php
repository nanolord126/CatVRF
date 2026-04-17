<?php

namespace Database\Factories\Food;

use App\Domains\Food\Models\DeliveryOrder;
use App\Domains\Food\Models\FoodOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryOrderFactory extends Factory
{
    protected $model = DeliveryOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'food_order_id' => FoodOrder::factory(),
            'courier_id' => null,
            'uuid' => \Illuminate\Support\Str::uuid(),
            'correlation_id' => \Illuminate\Support\Str::uuid(),
            'status' => $this->faker->randomElement([
                DeliveryOrder::STATUS_PENDING,
                DeliveryOrder::STATUS_ACCEPTED,
                DeliveryOrder::STATUS_ON_WAY,
                DeliveryOrder::STATUS_DELIVERED,
            ]),
            'customer_address' => $this->faker->address(),
            'delivery_lat' => $this->faker->latitude(55.5, 56.0),
            'delivery_lon' => $this->faker->longitude(37.0, 38.0),
            'delivery_point' => $this->faker->address(),
            'distance_km' => $this->faker->randomFloat(2, 1, 20),
            'eta_minutes' => $this->faker->numberBetween(15, 60),
            'picked_up_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'metadata' => [
                'external_delivery_id' => \Illuminate\Support\Str::uuid(),
                'estimated_time' => $this->faker->numberBetween(15, 60),
            ],
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryOrder::STATUS_PENDING,
        ]);
    }

    public function accepted(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryOrder::STATUS_ACCEPTED,
        ]);
    }

    public function onWay(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryOrder::STATUS_ON_WAY,
            'picked_up_at' => now()->subMinutes(10),
        ]);
    }

    public function delivered(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryOrder::STATUS_DELIVERED,
            'picked_up_at' => now()->subMinutes(30),
            'delivered_at' => now(),
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryOrder::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $this->faker->randomElement([
                'Customer request',
                'Restaurant unavailable',
                'Courier unavailable',
                'Payment failed',
            ]),
        ]);
    }
}
