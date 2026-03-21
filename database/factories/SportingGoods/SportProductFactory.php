<?php

declare(strict_types=1);

namespace Database\Factories\SportingGoods;

use App\Domains\SportingGoods\Models\SportProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SportProductFactory extends Factory
{
    protected $model = SportProduct::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->bothify('SPORT-####')),
            'sport_type' => $this->faker->randomElement(['football', 'basketball', 'tennis', 'swimming', 'running', 'cycling', 'gym', 'outdoor']),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(50000, 300000),
            'current_stock' => $this->faker->numberBetween(30, 200),
            'size_range' => json_encode(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
            'rating' => $this->faker->randomFloat(1, 3.5, 5),
            'review_count' => $this->faker->numberBetween(20, 300),
            'status' => 'active',
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            'tags' => ['sport', $this->faker->word()],
            'meta' => [],
        ];
    }

    public function premium(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'price' => $this->faker->numberBetween(200000, 500000),
                'rating' => $this->faker->randomFloat(1, 4.5, 5),
            ];
        });
    }

    public function budget(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'price' => $this->faker->numberBetween(50000, 100000),
            ];
        });
    }
}
