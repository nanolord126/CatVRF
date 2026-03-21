<?php

declare(strict_types=1);

namespace Database\Factories\Food;

use App\Domains\Food\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Restaurant>
 */
final class RestaurantFactory extends Factory
{
    protected $model = Restaurant::class;

    public function definition(): array
    {
        $cuisines = ['Italian', 'Japanese', 'Mexican', 'Indian', 'Chinese', 'Thai', 'French', 'Korean'];

        return [
            'tenant_id' => 1,
            'business_group_id' => null,
            'name' => $this->faker->company() . ' Restaurant',
            'description' => $this->faker->paragraph(3),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'cuisine_type' => [$this->faker->randomElement($cuisines)],
            'rating' => $this->faker->randomFloat(1, 3, 5),
            'review_count' => $this->faker->numberBetween(10, 500),
            'is_open' => $this->faker->boolean(80),
            'is_verified' => $this->faker->boolean(70),
            'accepts_delivery' => $this->faker->boolean(90),
            'correlation_id' => $this->faker->uuid(),
            'tags' => ['food', 'delivery'],
        ];
    }

    /**
     * Ресторан открыт
     */
    public function open(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_open' => true,
        ]);
    }

    /**
     * Ресторан закрыт
     */
    public function closed(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_open' => false,
        ]);
    }

    /**
     * Высокий рейтинг
     */
    public function highRating(): self
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->randomFloat(1, 4, 5),
            'review_count' => $this->faker->numberBetween(100, 500),
        ]);
    }

    /**
     * Низкий рейтинг
     */
    public function lowRating(): self
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->randomFloat(1, 2, 3.5),
            'review_count' => $this->faker->numberBetween(10, 50),
        ]);
    }
}
