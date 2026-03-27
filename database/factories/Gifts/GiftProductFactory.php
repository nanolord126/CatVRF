<?php

declare(strict_types=1);

namespace Database\Factories\Gifts;

use App\Domains\PartySupplies\Gifts\Models\GiftProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

final class GiftProductFactory extends Factory
{
    protected $model = GiftProduct::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->bothify('GFT-####')),
            'category' => $this->faker->randomElement(['experience', 'gadget', 'luxury', 'budget', 'romantic', 'kids']),
            'occasion' => $this->faker->randomElement(['birthday', 'anniversary', 'wedding', 'christmas', 'new_year', 'any']),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(50000, 300000),
            'gift_wrap_available' => true,
            'current_stock' => $this->faker->numberBetween(20, 200),
            'rating' => $this->faker->randomFloat(1, 3.5, 5),
            'review_count' => $this->faker->numberBetween(10, 200),
            'status' => 'active',
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            'tags' => ['gift', $this->faker->word()],
            'meta' => [],
        ];
    }

    public function luxury(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'luxury',
                'price' => $this->faker->numberBetween(200000, 500000),
                'rating' => $this->faker->randomFloat(1, 4.5, 5),
            ];
        });
    }

    public function budget(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'budget',
                'price' => $this->faker->numberBetween(50000, 100000),
            ];
        });
    }
}
