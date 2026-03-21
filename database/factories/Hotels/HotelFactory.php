<?php

declare(strict_types=1);

namespace Database\Factories\Hotels;

use App\Domains\Hotels\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hotel>
 */
final class HotelFactory extends Factory
{
    protected $model = Hotel::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'business_group_id' => null,
            'name' => $this->faker->company() . ' Hotel',
            'slug' => $this->faker->slug(),
            'description' => $this->faker->paragraph(3),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'stars' => $this->faker->numberBetween(2, 5),
            'rating' => $this->faker->randomFloat(1, 3, 5),
            'review_count' => $this->faker->numberBetween(10, 500),
            'is_open' => $this->faker->boolean(90),
            'is_verified' => $this->faker->boolean(75),
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'amenities' => ['wifi', 'parking', 'pool', 'gym'],
            'correlation_id' => $this->faker->uuid(),
            'tags' => ['hotel', 'accommodation'],
        ];
    }

    /**
     * Отель открыт
     */
    public function open(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_open' => true,
        ]);
    }

    /**
     * Люкс отель (5 звёзд)
     */
    public function luxury(): self
    {
        return $this->state(fn (array $attributes) => [
            'stars' => 5,
            'rating' => $this->faker->randomFloat(1, 4.5, 5),
            'review_count' => $this->faker->numberBetween(200, 500),
        ]);
    }

    /**
     * Бюджетный отель (2-3 звезды)
     */
    public function budget(): self
    {
        return $this->state(fn (array $attributes) => [
            'stars' => $this->faker->numberBetween(2, 3),
            'rating' => $this->faker->randomFloat(1, 3, 4),
            'review_count' => $this->faker->numberBetween(20, 100),
        ]);
    }
}
