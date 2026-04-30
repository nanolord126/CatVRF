<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\Factory;

final class CourierFactory extends Factory
{
    protected $model = Courier::class;

    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'tenant_id' => 1,
            'user_id' => 1,
            'vehicle_id' => null,
            'status' => fake()->randomElement([
                Courier::STATUS_ONLINE,
                Courier::STATUS_IDLE,
                Courier::STATUS_BUSY,
                Courier::STATUS_OFFLINE,
            ]),
            'current_location' => [
                'lat' => fake()->latitude(55.5, 56.0),
                'lng' => fake()->longitude(37.0, 38.0),
            ],
            'rating' => fake()->randomFloat(1, 3.5, 5.0),
            'commission_percent' => fake()->numberBetween(5, 15),
            'tags' => [
                'vehicle' => fake()->randomElement(['bike', 'car', 'scooter']),
            ],
            'vehicle_type' => fake()->randomElement([
                Courier::VEHICLE_BIKE,
                Courier::VEHICLE_CAR,
                Courier::VEHICLE_SCOOTER,
                Courier::VEHICLE_PEDESTRIAN,
            ]),
            'current_lat' => fake()->latitude(55.5, 56.0),
            'current_lng' => fake()->longitude(37.0, 38.0),
            'capacity_kg' => fake()->randomElement([5, 10, 20, 50]),
            'is_taxi_driver' => fake()->boolean(20), // 20% chance
            'battery_level' => fake()->optional(0.7)->numberBetween(20, 100),
            'last_location_update' => fake()->dateTimeBetween('-1 hour', 'now'),
            'correlation_id' => fake()->uuid(),
        ];
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Courier::STATUS_ONLINE,
        ]);
    }

    public function idle(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Courier::STATUS_IDLE,
        ]);
    }

    public function taxiDriver(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_taxi_driver' => true,
            'vehicle_type' => Courier::VEHICLE_TAXI,
            'capacity_kg' => 50,
        ]);
    }

    public function withLocation(float $lat, float $lng): static
    {
        return $this->state(fn (array $attributes) => [
            'current_lat' => $lat,
            'current_lng' => $lng,
            'current_location' => ['lat' => $lat, 'lng' => $lng],
        ]);
    }
}
