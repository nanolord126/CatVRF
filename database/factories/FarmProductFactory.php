<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\FarmDirect\Models\FarmProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class FarmProductFactory extends Factory
{
    protected $model = FarmProduct::class;

    public function definition(): array
    {
        return [
            "uuid" => fake()->uuid(),
            "tenant_id" => fake()->numberBetween(1, 10),
            "farm_id" => fake()->numberBetween(1, 10),
            "name" => fake()->word(),
            "price" => fake()->numberBetween(100, 1000),
            "correlation_id" => fake()->uuid(),
        ];
    }
}

