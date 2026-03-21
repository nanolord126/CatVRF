<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\FarmDirect\Models\FarmOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class FarmOrderFactory extends Factory
{
    protected $model = FarmOrder::class;

    public function definition(): array
    {
        return [
            "uuid" => fake()->uuid(),
            "tenant_id" => fake()->numberBetween(1, 10),
            "client_id" => fake()->numberBetween(1, 10),
            "farm_id" => fake()->numberBetween(1, 10),
            "delivery_address" => fake()->address(),
            "total_amount" => fake()->numberBetween(1000, 5000),
            "correlation_id" => fake()->uuid(),
        ];
    }
}

