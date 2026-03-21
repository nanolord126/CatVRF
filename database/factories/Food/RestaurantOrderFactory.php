<?php

declare(strict_types=1);

namespace Database\Factories\Food;

use App\Domains\Food\Models\RestaurantOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RestaurantOrder>
 */
final class RestaurantOrderFactory extends Factory
{
    protected $model = RestaurantOrder::class;

    public function definition(): array
    {
        $statuses = ['pending', 'confirmed', 'ready', 'delivered'];
        $paymentStatuses = ['pending', 'paid', 'refunded'];

        return [
            'tenant_id' => 1,
            'restaurant_id' => 1,
            'user_id' => 1,
            'order_number' => 'ORD-' . strtoupper($this->faker->bothify('??########')),
            'items_json' => [
                [
                    'name' => $this->faker->word(),
                    'quantity' => $this->faker->numberBetween(1, 5),
                    'price' => $this->faker->numberBetween(500, 5000),
                ],
            ],
            'subtotal_amount' => $this->faker->numberBetween(1000, 10000),
            'delivery_fee' => $this->faker->numberBetween(100, 500),
            'discount_amount' => $this->faker->numberBetween(0, 500),
            'total_amount' => $this->faker->numberBetween(1500, 10500),
            'status' => $this->faker->randomElement($statuses),
            'payment_status' => $this->faker->randomElement($paymentStatuses),
            'delivery_address' => $this->faker->address(),
            'delivery_time_minutes' => $this->faker->numberBetween(30, 60),
            'estimated_delivery_at' => now()->addMinutes($this->faker->numberBetween(30, 60)),
            'notes' => $this->faker->text(100),
            'correlation_id' => $this->faker->uuid(),
            'tags' => ['food', 'delivery'],
        ];
    }

    /**
     * Заказ в статусе "pending"
     */
    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Заказ в статусе "delivered"
     */
    public function delivered(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Заказ в статусе "cancelled"
     */
    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'payment_status' => 'refunded',
        ]);
    }
}
