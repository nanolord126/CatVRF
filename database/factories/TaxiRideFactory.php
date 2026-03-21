<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Auto\Models\TaxiRide;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Auto\Models\TaxiRide>
 */
final class TaxiRideFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaxiRide::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'correlation_id' => (string) Str::uuid(),
            'driver_id' => User::factory(),
            'passenger_id' => User::factory(),
            'vehicle_class' => fake()->randomElement(['economy', 'comfort', 'premium']),
            'pickup_lat' => fake()->latitude(),
            'pickup_lng' => fake()->longitude(),
            'dropoff_lat' => fake()->latitude(),
            'dropoff_lng' => fake()->longitude(),
            'distance_km' => fake()->numberBetween(1, 100),
            'fare_amount' => fake()->numberBetween(50000, 500000),
            'surge_multiplier' => fake()->randomFloat(1, 1, 3),
            'status' => 'requested',
            'tags' => ['ride:active', 'source:factory'],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'payment_status' => 'completed',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
