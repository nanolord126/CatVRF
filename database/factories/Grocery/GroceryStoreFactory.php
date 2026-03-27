<?php

declare(strict_types=1);

namespace Database\Factories\Grocery;

use App\Domains\Food\Grocery\Models\GroceryStore;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class GroceryStoreFactory extends Factory
{
    protected $model = GroceryStore::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'store_type' => fake()->randomElement(['supermarket', 'vegetable', 'meat', 'cafe']),
            'kitchen_type' => fake()->randomElement(['italian', 'asian', 'russian', 'european']),
            'is_active' => true,
            'correlation_id' => Str::uuid()->toString(),
        ];
    }
}
