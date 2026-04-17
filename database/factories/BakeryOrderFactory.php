<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Confectionery\Models\BakeryOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class BakeryOrderFactory extends Factory
{
    protected $model = BakeryOrder::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'tenant_id' => fake()->numberBetween(1, 10),
            'business_group_id' => null,
            'customer_id' => fake()->numberBetween(1, 100),
            'status' => fake()->randomElement(['pending', 'confirmed', 'completed']),
            'total_price' => fake()->randomFloat(2, 500, 15000),
            'delivery_date' => fake()->dateTimeBetween('now', '+14 days'),
            'notes' => fake()->optional()->sentence(),
            'tags' => ['source:factory'],
        ];
    }
}
