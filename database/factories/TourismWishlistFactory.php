<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Travel\Models\TourismWishlist;
use App\Domains\Travel\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Tourism Wishlist Factory
 * 
 * Factory for creating tourism wishlist test data.
 * Includes state methods for different wishlist item types.
 */
final class TourismWishlistFactory extends Factory
{
    protected $model = TourismWishlist::class;

    public function definition(): array
    {
        $tour = Tour::factory()->create();

        return [
            'uuid' => fake()->uuid(),
            'tenant_id' => 1,
            'user_id' => 1,
            'tour_id' => $tour->id,
            'priority' => fake()->numberBetween(1, 10),
            'notes' => fake()->optional(0.3)->sentence(),
            'budget_range' => fake()->optional(0.5)->randomElements([50000, 100000, 200000, 500000], 2),
            'preferred_dates' => fake()->optional(0.4)->randomElements([
                now()->addDays(7)->toDateString(),
                now()->addDays(14)->toDateString(),
                now()->addDays(30)->toDateString(),
            ], fake()->numberBetween(1, 3)),
            'group_size' => fake()->optional(0.6)->numberBetween(1, 10),
            'special_requests' => fake()->optional(0.2)->sentence(),
            'metadata' => [
                'tour_title' => $tour->title,
                'tour_destination' => $tour->destination->name ?? 'Unknown',
                'tour_price' => $tour->base_price,
                'tour_duration' => $tour->duration_days,
                'added_from' => fake()->randomElement(['manual', 'recommendation', 'search']),
            ],
        ];
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(8, 10),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'priority_reason' => fake()->randomElement(['dream trip', 'special occasion', 'anniversary']),
            ]),
        ]);
    }

    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(1, 3),
        ]);
    }

    public function withBudget(): static
    {
        return $this->state(fn (array $attributes) => [
            'budget_range' => [
                fake()->numberBetween(50000, 100000),
                fake()->numberBetween(200000, 500000),
            ],
        ]);
    }

    public function withPreferredDates(): static
    {
        return $this->state(fn (array $attributes) => [
            'preferred_dates' => [
                now()->addDays(fake()->numberBetween(7, 30))->toDateString(),
                now()->addDays(fake()->numberBetween(31, 60))->toDateString(),
            ],
        ]);
    }

    public function withGroupSize(): static
    {
        return $this->state(fn (array $attributes) => [
            'group_size' => fake()->numberBetween(2, 10),
        ]);
    }

    public function corporate(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(7, 10),
            'group_size' => fake()->numberBetween(10, 50),
            'special_requests' => fake()->randomElement([
                'Team building event',
                'Corporate retreat',
                'Annual meeting',
            ]),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'type' => 'corporate',
                'company_name' => fake()->company(),
            ]),
        ]);
    }

    public function family(): static
    {
        return $this->state(fn (array $attributes) => [
            'group_size' => fake()->numberBetween(2, 6),
            'special_requests' => fake()->randomElement([
                'Child-friendly activities',
                'Accessible for elderly',
                'Vegetarian options',
            ]),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'type' => 'family',
            ]),
        ]);
    }
}
