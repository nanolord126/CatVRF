<?php

declare(strict_types=1);

namespace Database\Factories\Jewelry;

use App\Domains\Luxury\Jewelry\Models\JewelryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

final class JewelryItemFactory extends Factory
{
    protected $model = JewelryItem::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->bothify('JWL-####')),
            'category' => $this->faker->randomElement(['ring', 'necklace', 'bracelet', 'earring', 'pendant', 'watch']),
            'metal' => $this->faker->randomElement(['gold', 'silver', 'platinum', 'rose_gold', 'white_gold']),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(50000, 1000000),
            'weight_grams' => $this->faker->randomFloat(1, 1, 100),
            'purity' => $this->faker->randomElement(['585', '750', '925', '950', '999']),
            'current_stock' => $this->faker->numberBetween(1, 20),
            'certificate_required' => $this->faker->boolean(),
            'certificate_type' => $this->faker->randomElement(['GIA', 'IGI', 'HRD']),
            'rating' => $this->faker->randomFloat(1, 3.5, 5),
            'review_count' => $this->faker->numberBetween(10, 300),
            'status' => 'active',
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            'tags' => ['jewelry', $this->faker->word()],
            'meta' => [],
        ];
    }

    public function luxury(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'metal' => 'platinum',
                'purity' => '950',
                'price' => $this->faker->numberBetween(500000, 1000000),
                'certificate_required' => true,
                'rating' => $this->faker->randomFloat(1, 4.7, 5),
            ];
        });
    }

    public function affordable(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'metal' => 'silver',
                'purity' => '925',
                'price' => $this->faker->numberBetween(50000, 200000),
                'certificate_required' => false,
            ];
        });
    }
}
