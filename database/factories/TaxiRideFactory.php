<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Taxi\TaxiRide>
 */
class TaxiRideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'driver_id' => \App\Models\User::factory(),
            'passenger_id' => \App\Models\User::factory(),
            'vehicle_class' => $this->faker->randomElement(['economy', 'comfort', 'premium']),
            'pickup_lat' => $this->faker->latitude(),
            'pickup_lng' => $this->faker->longitude(),
            'dropoff_lat' => $this->faker->latitude(),
            'dropoff_lng' => $this->faker->longitude(),
            'distance_km' => $this->faker->numberBetween(1, 100),
            'fare_amount' => $this->faker->numberBetween(50, 5000),
            'status' => $this->faker->randomElement(['requested', 'accepted', 'completed', 'cancelled']),
        ];
    }
}
