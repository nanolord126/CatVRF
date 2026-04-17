<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Education\Models\SlotBooking;
use App\Domains\Education\Models\Slot;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SlotBookingFactory extends Factory
{
    protected $model = SlotBooking::class;

    public function definition(): array
    {
        return [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 1,
            'business_group_id' => null,
            'user_id' => \App\Models\User::factory(),
            'slot_id' => Slot::factory(),
            'booking_reference' => 'EDU-' . strtoupper(\Illuminate\Support\Str::random(8)) . '-' . now()->format('Ymd'),
            'status' => 'confirmed',
            'booked_at' => now(),
            'confirmed_at' => now(),
            'cancelled_at' => null,
            'attended_at' => null,
            'biometric_hash' => $this->faker->sha256(),
            'device_fingerprint' => hash('sha256', $this->faker->ipv4() . $this->faker->userAgent()),
            'metadata' => json_encode(['is_corporate' => false]),
            'correlation_id' => \Illuminate\Support\Str::uuid(),
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'confirmed_at' => null,
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'attended_at' => now(),
        ]);
    }

    public function b2b(): self
    {
        return $this->state(fn (array $attributes) => [
            'business_group_id' => 1,
            'metadata' => json_encode(['is_corporate' => true]),
        ]);
    }
}
