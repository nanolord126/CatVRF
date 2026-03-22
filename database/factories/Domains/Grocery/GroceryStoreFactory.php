<?php declare(strict_types=1);

namespace Database\Factories\Domains\Grocery;

use App\Domains\Grocery\Models\GroceryStore;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class GroceryStoreFactory extends Factory
{
    protected $model = GroceryStore::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'uuid' => Str::uuid(),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'store_type' => fake()->randomElement(['supermarket', 'cafe', 'butcher', 'greengrocer']),
            'cuisines' => ['italian', 'japanese'],
            'delivery_zones' => [],
            'is_active' => true,
            'rating' => fake()->randomFloat(1, 3.5, 5.0),
            'correlation_id' => Str::uuid(),
            'tags' => ['fast_delivery', 'organic'],
        ];
    }
}
