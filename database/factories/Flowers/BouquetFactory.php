<?php

declare(strict_types=1);

namespace Database\Factories\Flowers;

use App\Domains\Flowers\Models\Bouquet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class BouquetFactory extends Factory
{
    protected $model = Bouquet::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'category_id' => 1,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(200000, 1000000),
            'stock_quantity' => fake()->numberBetween(5, 50),
            'image_url' => fake()->imageUrl(),
            'is_available' => true,
            'correlation_id' => Str::uuid()->toString(),
        ];
    }
}
