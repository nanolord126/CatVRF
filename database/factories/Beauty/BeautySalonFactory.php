<?php

declare(strict_types=1);

namespace Database\Factories\Beauty;

use App\Domains\Beauty\Models\BeautySalon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory для модели BeautySalon.
 * Production 2026 - полная реализация.
 *
 * @extends Factory<BeautySalon>
 */
final class BeautySalonFactory extends Factory
{
    protected $model = BeautySalon::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1, // Default tenant
            'business_group_id' => null,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'name' => $this->faker->company() . ' ' . $this->faker->randomElement(['Beauty Salon', 'Hair Studio', 'Spa Center']),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'description' => $this->faker->paragraph(3),
            'working_hours' => [
                'monday' => ['open' => '09:00', 'close' => '21:00'],
                'tuesday' => ['open' => '09:00', 'close' => '21:00'],
                'wednesday' => ['open' => '09:00', 'close' => '21:00'],
                'thursday' => ['open' => '09:00', 'close' => '21:00'],
                'friday' => ['open' => '09:00', 'close' => '22:00'],
                'saturday' => ['open' => '10:00', 'close' => '22:00'],
                'sunday' => ['open' => '10:00', 'close' => '20:00'],
            ],
            'geo_point' => [
                'latitude' => $this->faker->latitude(55.5, 56.0),
                'longitude' => $this->faker->longitude(37.0, 38.0),
            ],
            'rating' => $this->faker->randomFloat(2, 3.5, 5.0),
            'review_count' => $this->faker->numberBetween(5, 200),
            'is_verified' => $this->faker->boolean(70),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'inactive']),
            'tags' => [
                $this->faker->randomElement(['premium', 'budget', 'luxury', 'express']),
                $this->faker->randomElement(['nails', 'hair', 'spa', 'massage', 'makeup']),
            ],
            'metadata' => [
                'source' => $this->faker->randomElement(['manual', 'dikidi_migration', 'api']),
                'features' => $this->faker->randomElements(['wifi', 'parking', 'coffee', 'loyalty_program'], $this->faker->numberBetween(1, 3)),
            ],
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
