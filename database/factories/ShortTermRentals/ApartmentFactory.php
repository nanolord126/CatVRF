<?php

declare(strict_types=1);

namespace Database\Factories\ShortTermRentals;

use App\Domains\ShortTermRentals\Models\Apartment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class ApartmentFactory extends Factory
{
    protected $model = Apartment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'price_per_night' => fake()->numberBetween(300000, 2000000),
            'bedrooms' => fake()->numberBetween(1, 4),
            'bathrooms' => fake()->numberBetween(1, 3),
            'max_guests' => fake()->numberBetween(2, 8),
            'is_available' => true,
            'correlation_id' => Str::uuid()->toString(),
        ];
    }
}
