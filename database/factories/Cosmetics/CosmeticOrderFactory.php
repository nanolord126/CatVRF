<?php

declare(strict_types=1);

namespace Database\Factories\Cosmetics;

use App\Domains\Cosmetics\Models\CosmeticOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

final class CosmeticOrderFactory extends Factory
{
    protected $model = CosmeticOrder::class;

    public function definition(): array
    {
        return [
            'order_number' => 'COS-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'items_json' => json_encode([
                ['product_id' => 1, 'quantity' => 1, 'price' => 150000],
            ]),
            'subtotal_amount' => 150000,
            'discount_amount' => 0,
            'total_amount' => 150000,
            'status' => 'pending',
            'payment_status' => 'pending',
            'shipping_address' => $this->faker->address(),
            'ordered_at' => now(),
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            'tags' => ['order'],
            'meta' => [],
        ];
    }

    public function pending(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'payment_status' => 'pending',
            ];
        });
    }

    public function delivered(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'delivered',
                'payment_status' => 'paid',
            ];
        });
    }

    public function cancelled(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
                'payment_status' => 'refunded',
            ];
        });
    }
}
