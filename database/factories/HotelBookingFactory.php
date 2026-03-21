<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Hotel\HotelBooking>
 */
final class HotelBookingFactory extends Factory
{
    protected $model = \App\Models\Domains\Hotel\HotelBooking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('now', '+30 days');
        $checkOut = Carbon::instance($checkIn)->addDays(fake()->numberBetween(1, 14));

        return [
            'tenant_id' => Tenant::factory(),
            'hotel_id' => User::factory(),
            'room_id' => null,
            'guest_id' => User::factory(),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_price' => fake()->numberBetween(50000, 500000),
            'status' => 'pending',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'checked_in',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'checked_out',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
