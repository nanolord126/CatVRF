<?php

declare(strict_types=1);

namespace Database\Factories\Hotels;

use App\Domains\Hotels\Models\HotelBooking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HotelBooking>
 */
final class HotelBookingFactory extends Factory
{
    protected $model = HotelBooking::class;

    public function definition(): array
    {
        $checkIn = now()->addDays($this->faker->numberBetween(1, 30));
        $checkOut = $checkIn->addDays($this->faker->numberBetween(1, 14));
        $nights = $checkOut->diffInDays($checkIn);
        $pricePerNight = $this->faker->numberBetween(5000, 50000);
        $totalCost = $pricePerNight * $nights;

        return [
            'tenant_id' => 1,
            'hotel_id' => 1,
            'user_id' => 1,
            'booking_number' => 'BK-' . strtoupper($this->faker->bothify('??########')),
            'room_type_id' => 1,
            'guest_name' => $this->faker->name(),
            'guest_email' => $this->faker->email(),
            'guest_phone' => $this->faker->phoneNumber(),
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'number_of_guests' => $this->faker->numberBetween(1, 4),
            'number_of_nights' => $nights,
            'room_price_per_night' => $pricePerNight,
            'total_nights_cost' => $totalCost,
            'deposit_amount' => (int) ($totalCost * 0.2),
            'total_cost' => $totalCost,
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'completed']),
            'payment_status' => $this->faker->randomElement(['pending', 'paid']),
            'special_requests' => $this->faker->boolean(60) ? $this->faker->text(100) : '',
            'correlation_id' => $this->faker->uuid(),
            'tags' => ['hotel', 'booking'],
        ];
    }

    /**
     * Бронь в статусе "pending"
     */
    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Бронь в статусе "confirmed"
     */
    public function confirmed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Бронь в статусе "completed"
     */
    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);
    }
}
