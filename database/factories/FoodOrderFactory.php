<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Food\FoodOrder>
 */
class FoodOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'restaurant_id' => \App\Models\User::factory(),
            'customer_id' => \App\Models\User::factory(),
            'total_amount' => $this->faker->numberBetween(100, 5000),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'delivered', 'cancelled']),
            'items' => json_encode([['name' => 'Item', 'qty' => 1]]),
            'delivery_address' => $this->faker->address(),
        ];
    }
}
