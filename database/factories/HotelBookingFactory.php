<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Hotel\HotelBooking>
 */
class HotelBookingFactory extends Factory
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
            'hotel_id' => \App\Models\User::factory(),
            'room_id' => null,
            'guest_id' => \App\Models\User::factory(),
            'check_in' => $this->faker->dateTimeBetween('now', '+30 days'),
            'check_out' => $this->faker->dateTimeBetween('+31 days', '+60 days'),
            'total_price' => $this->faker->numberBetween(1000, 50000),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled']),
        ];
    }
}
