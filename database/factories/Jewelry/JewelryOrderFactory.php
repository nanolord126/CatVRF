<?php

declare(strict_types=1);

namespace Database\Factories\Jewelry;

use App\Domains\Luxury\Jewelry\Models\JewelryOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

final class JewelryOrderFactory extends Factory
{
    protected $model = JewelryOrder::class;

    public function definition(): array
    {
        return [
            'order_number' => 'JWL-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'item_id' => 1,
            'quantity' => 1,
            'unit_price' => 300000,
            'total_price' => 300000,
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

    public function shipped(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'shipped',
                'payment_status' => 'paid',
            ];
        });
    }
}
