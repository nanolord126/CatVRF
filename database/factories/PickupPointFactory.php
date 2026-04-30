<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Logistics\Models\PickupPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PickupPoint>
 */
final class PickupPointFactory extends Factory
{
    protected $model = PickupPoint::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Moscow coordinates as default
        $lat = 55.75 + (fake()->randomFloat(-0.1, 0.1));
        $lng = 37.62 + (fake()->randomFloat(-0.1, 0.1));

        return [
            'uuid' => fake()->uuid(),
            'tenant_id' => 1,
            'name' => fake()->company().' ПВЗ',
            'address' => fake()->address(),
            'lat' => $lat,
            'lng' => $lng,
            'capacity_slots' => fake()->numberBetween(50, 200),
            'current_load' => fake()->numberBetween(0, 50),
            'working_hours' => fake()->randomElement(['09:00-21:00', '08:00-22:00', '10:00-20:00']),
            'is_24h' => fake()->boolean(10), // 10% chance of 24/7
            'status' => fake()->randomElement(['active', 'active', 'active', 'inactive', 'maintenance']), // weighted towards active
            'phone' => fake()->phoneNumber(),
            'metadata' => [
                'has_lockers' => fake()->boolean(),
                'has_payment_terminal' => fake()->boolean(),
                'has_packing_service' => fake()->boolean(),
            ],
            'correlation_id' => fake()->uuid(),
        ];
    }

    /**
     * State for 24/7 pickup points
     */
    public function is24h(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_24h' => true,
            'working_hours' => '00:00-23:59',
        ]);
    }

    /**
     * State for active pickup points
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * State for near-overloaded pickup points (>85% load)
     */
    public function nearOverload(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_load' => (int) ($attributes['capacity_slots'] * 0.9),
        ]);
    }

    /**
     * State for pickup points with full services
     */
    public function withFullServices(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => [
                'has_lockers' => true,
                'has_payment_terminal' => true,
                'has_packing_service' => true,
                'has_qr_scanner' => true,
            ],
        ]);
    }
}
