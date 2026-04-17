<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Restaurant\Models\Reservation;
use App\Domains\Restaurant\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $restaurant = Restaurant::factory()->create();
        $user = User::factory()->create();

        return [
            'tenant_id' => 1,
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'uuid' => Str::uuid()->toString(),
            'reservation_date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'reservation_time' => fake()->time('H:i'),
            'party_size' => fake()->numberBetween(1, 10),
            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled', 'completed']),
            'special_requests' => fake()->randomElement([null, fake()->sentence()]),
            'contact_phone' => fake()->phoneNumber(),
            'contact_email' => fake()->email(),
            'confirmation_code' => strtoupper(substr(md5(uniqid()), 0, 8)),
            'is_confirmed' => fake()->boolean(50),
            'correlation_id' => Str::uuid()->toString(),
            'metadata' => json_encode([
                'source' => fake()->randomElement(['web', 'mobile', 'api']),
            ]),
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'is_confirmed' => false,
        ]);
    }

    public function confirmed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'is_confirmed' => true,
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
