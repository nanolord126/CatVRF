<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Confectionery\Models\Cake;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class CakeFactory extends Factory
{
    protected $model = Cake::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'tenant_id' => fake()->numberBetween(1, 10),
            'business_group_id' => null,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 300, 8000),
            'weight_grams' => fake()->numberBetween(500, 5000),
            'layers' => fake()->numberBetween(1, 5),
            'flavour' => fake()->randomElement(['vanilla', 'chocolate', 'berry', 'caramel']),
            'status' => fake()->randomElement(['draft', 'active', 'archived']),
            'tags' => ['source:factory'],
        ];
    }
}
