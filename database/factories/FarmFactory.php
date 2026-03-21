<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\FarmDirect\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

class FarmFactory extends Factory
{
    protected $model = Farm::class;

    public function definition(): array
    {
        return [
            "uuid" => fake()->uuid(),
            "tenant_id" => fake()->numberBetween(1, 10),
            "name" => fake()->company(),
            "correlation_id" => fake()->uuid(),
        ];
    }
}

