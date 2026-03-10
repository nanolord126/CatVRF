<?php

namespace Database\Factories;

use App\Models\Domains\Delivery\DeliveryOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryOrderFactory extends Factory
{
    protected $model = DeliveryOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'customer_id' => User::factory(),
            'driver_id' => User::factory(),
            'pickup_address' => $this->faker->address(),
            'delivery_address' => $this->faker->address(),
            'distance' => $this->faker->numberBetween(1, 100),
            'amount' => $this->faker->numberBetween(50, 5000),
            'status' => $this->faker->randomElement(['pending', 'assigned', 'in_transit', 'delivered', 'cancelled']),
        ];
    }
}
