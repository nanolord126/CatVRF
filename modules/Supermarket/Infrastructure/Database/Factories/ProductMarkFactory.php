<?php

declare(strict_types=1);

namespace Modules\Supermarket\Database\Factories;

use Modules\Supermarket\Infrastructure\Models\ProductMark;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductMarkFactory extends Factory
{
    protected $model = ProductMark::class;

    public function definition(): array
    {
        return [
            'product_id' => \App\Models\Product::factory(),
            'data_matrix_code' => fake()->bothify('01##################'),
            'gtin' => fake()->numerify('#############'),
            'serial_number' => fake->bothify('????-####-????'),
            'batch_number' => fake->bothify('BATCH-####'),
            'expiry_date' => now()->addDays(fake()->numberBetween(30, 365)),
            'status' => fake()->randomElement(['active', 'withdrawn', 'sold']),
            'withdrawn_at' => null,
            'sold_at' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'withdrawn_at' => null,
            'sold_at' => null,
        ]);
    }

    public function withdrawn(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'withdrawn',
            'withdrawn_at' => now()->subHours(fake()->numberBetween(1, 24)),
        ]);
    }

    public function sold(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sold',
            'sold_at' => now()->subHours(fake()->numberBetween(1, 72)),
        ]);
    }
}
