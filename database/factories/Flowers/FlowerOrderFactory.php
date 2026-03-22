<?php

declare(strict_types=1);

namespace Database\Factories\Flowers;

use App\Domains\Flowers\Models\FlowerOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class FlowerOrderFactory extends Factory
{
    protected $model = FlowerOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'user_id' => 1,
            'bouquet_id' => 1,
            'quantity' => fake()->numberBetween(1, 3),
            'total_price' => fake()->numberBetween(200000, 500000),
            'status' => 'pending',
            'delivery_address' => fake()->address(),
            'delivery_at' => fake()->dateTimeBetween('now', '+7 days'),
            'payment_status' => 'pending',
            'correlation_id' => Str::uuid()->toString(),
        ];
    }
}
