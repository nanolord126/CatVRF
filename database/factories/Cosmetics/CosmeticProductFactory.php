<?php

declare(strict_types=1);

namespace Database\Factories\Cosmetics;

use App\Domains\Cosmetics\Models\CosmeticProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

final class CosmeticProductFactory extends Factory
{
    protected $model = CosmeticProduct::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->bothify('COSM-####')),
            'brand' => $this->faker->randomElement(['Estée Lauder', 'L\'Oréal', 'MAC', 'Clinique', 'Dior', 'Chanel', 'Bobbi Brown', 'Maybelline']),
            'category' => $this->faker->randomElement(['foundation', 'lipstick', 'mascara', 'eyeshadow', 'blush', 'perfume', 'skincare', 'nail_polish']),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(50000, 500000),
            'current_stock' => $this->faker->numberBetween(10, 500),
            'min_stock_threshold' => 20,
            'skin_type' => $this->faker->randomElement(['all', 'oily', 'dry', 'combination', 'sensitive']),
            'cruelty_free' => $this->faker->boolean(),
            'natural' => $this->faker->boolean(),
            'rating' => $this->faker->randomFloat(1, 3, 5),
            'review_count' => $this->faker->numberBetween(10, 500),
            'status' => 'active',
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            'tags' => ['cosmetics', $this->faker->word()],
            'meta' => [],
        ];
    }

    public function luxury(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'brand' => $this->faker->randomElement(['Dior', 'Chanel', 'Tom Ford']),
                'price' => $this->faker->numberBetween(200000, 800000),
                'cruelty_free' => true,
                'rating' => $this->faker->randomFloat(1, 4.5, 5),
            ];
        });
    }

    public function drugstore(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'brand' => $this->faker->randomElement(['Maybelline', 'L\'Oréal', 'Rimmel']),
                'price' => $this->faker->numberBetween(20000, 80000),
                'rating' => $this->faker->randomFloat(1, 3.5, 4.5),
            ];
        });
    }
}
