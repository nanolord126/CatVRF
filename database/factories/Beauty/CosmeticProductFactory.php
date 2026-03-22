<?php

declare(strict_types=1);

namespace Database\Factories\Beauty;

use App\Domains\Beauty\Models\CosmeticProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class CosmeticProductFactory extends Factory
{
    protected $model = CosmeticProduct::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'salon_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'brand' => fake()->company(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['shampoo', 'conditioner', 'mask', 'serum', 'oil']),
            'volume' => fake()->randomElement(['50ml', '100ml', '250ml', '500ml']),
            'price' => fake()->randomFloat(2, 50, 500),
            'stock' => fake()->numberBetween(0, 100),
            'is_available' => true,
            'is_professional' => fake()->boolean(),
            'correlation_id' => Str::uuid()->toString(),
        ];
    }
}
