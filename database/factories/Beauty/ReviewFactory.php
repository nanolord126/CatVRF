<?php

declare(strict_types=1);

namespace Database\Factories\Beauty;

use App\Domains\Beauty\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Review>
 */
final class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'salon_id' => $this->faker->optional()->numberBetween(1, 10),
            'master_id' => $this->faker->optional()->numberBetween(1, 50),
            'appointment_id' => $this->faker->optional()->numberBetween(1, 100),
            'client_id' => $this->faker->numberBetween(1, 100),
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'rating' => $this->faker->numberBetween(3, 5),
            'comment' => $this->faker->paragraph(2),
            'is_verified' => $this->faker->boolean(80),
            'tags' => [
                $this->faker->randomElement(['quality', 'service', 'cleanliness', 'price']),
            ],
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    public function topRated(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 5,
            'is_verified' => true,
        ]);
    }
}
