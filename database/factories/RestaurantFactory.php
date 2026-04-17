<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Restaurant\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class RestaurantFactory extends Factory
{
    protected $model = Restaurant::class;

    public function definition(): array
    {
        $categories = [
            'fine_dining', 'casual', 'fast_food', 'cafe', 'bistro',
            'bar', 'pub', 'steakhouse', 'sushi', 'pizzeria',
            'seafood', 'vegetarian', 'vegan', 'bakery', 'coffee_shop',
            'food_truck', 'buffet', 'gastropub', 'brasserie', 'tea_house',
        ];

        $cuisines = [
            'italian', 'japanese', 'chinese', 'french', 'russian',
            'mexican', 'thai', 'indian', 'american', 'georgian',
            'armenian', 'mediterranean', 'european', 'asian', 'fusion',
        ];

        return [
            'tenant_id' => 1,
            'business_group_id' => null,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'name' => fake()->company() . ' Restaurant',
            'description' => fake()->paragraph(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'lat' => fake()->latitude(-90, 90),
            'lon' => fake()->longitude(-180, 180),
            'category' => fake()->randomElement($categories),
            'cuisine_type' => fake()->randomElement($cuisines),
            'price_range' => fake()->randomElement(['$', '$$', '$$$', '$$$$']),
            'rating' => fake()->randomFloat(1, 3, 5),
            'review_count' => fake()->numberBetween(0, 1000),
            'is_delivery_available' => fake()->boolean(),
            'is_pickup_available' => fake()->boolean(),
            'is_dine_in_available' => fake()->boolean(80),
            'average_preparation_time_minutes' => fake()->numberBetween(15, 60),
            'status' => fake()->randomElement(['active', 'inactive', 'verified']),
            'tags' => fake()->randomElements(['wifi', 'parking', 'terrace', 'ac', 'outdoor'], fake()->numberBetween(0, 3)),
            'metadata' => json_encode([
                'website' => fake()->url(),
                'phone' => fake()->phoneNumber(),
            ]),
        ];
    }

    public function fineDining(): self
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'fine_dining',
            'price_range' => '$$$',
            'rating' => fake()->randomFloat(1, 4, 5),
        ]);
    }

    public function casual(): self
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'casual',
            'price_range' => '$$',
        ]);
    }

    public function fastFood(): self
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'fast_food',
            'price_range' => '$',
            'is_delivery_available' => true,
            'is_pickup_available' => true,
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function verified(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
        ]);
    }
}
