<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Travel\Models\TravelBooking;
use Illuminate\Database\Eloquent\Factories\Factory;

final class TravelBookingFactory extends Factory
{
    protected $model = TravelBooking::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'correlation_id' => $this->faker->uuid(),
            'tour_id' => 1,
            'traveler_name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'traveler_email' => $this->faker->email(),
            'traveler_phone' => $this->faker->phoneNumber(),
            'booking_date' => $this->faker->dateTime(),
            'departure_date' => $this->faker->dateTimeBetween('+7 days', '+90 days'),
            'participants' => $this->faker->numberBetween(1, 4),
            'total_price' => $this->faker->numberBetween(300000, 1000000),
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
