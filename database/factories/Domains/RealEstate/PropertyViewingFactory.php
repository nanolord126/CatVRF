<?php

declare(strict_types=1);

namespace Database\Factories\Domains\RealEstate;

use App\Domains\RealEstate\Models\PropertyViewing;
use App\Domains\RealEstate\Models\Property;
use App\Models\Domains\RealEstate\RealEstateAgent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class PropertyViewingFactory extends Factory
{
    protected $model = PropertyViewing::class;

    public function definition(): array
    {
        $scheduledAt = $this->faker->dateTimeBetween('+1 day', '+30 days');
        $status = $this->faker->randomElement(['pending', 'held', 'confirmed', 'completed', 'cancelled', 'no_show']);
        $isB2B = $this->faker->boolean(30);

        return [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => Property::factory()->create()->tenant_id,
            'business_group_id' => null,
            'property_id' => Property::factory(),
            'user_id' => User::factory(),
            'agent_id' => RealEstateAgent::factory(),
            'scheduled_at' => $scheduledAt,
            'held_at' => $status === 'held' ? now() : null,
            'hold_expires_at' => $status === 'held' ? now()->addMinutes($isB2B ? 60 : 15) : null,
            'completed_at' => $status === 'completed' ? $scheduledAt->copy()->addHours(1) : null,
            'cancelled_at' => in_array($status, ['cancelled', 'no_show']) ? $scheduledAt->copy()->subHours(rand(1, 24)) : null,
            'status' => $status,
            'is_b2b' => $isB2B,
            'webrtc_room_id' => 'room_' . md5($this->faker->randomNumber() . $scheduledAt->format('Y-m-d H:i')),
            'faceid_verified' => $this->faker->boolean(70),
            'cancellation_reason' => in_array($status, ['cancelled', 'no_show']) ? $this->faker->randomElement(['client_cancelled', 'agent_cancelled', 'no_show']) : null,
            'correlation_id' => \Illuminate\Support\Str::uuid(),
            'metadata' => [
                'preferred_contact_method' => $this->faker->randomElement(['phone', 'email', 'wechat', 'telegram']),
                'number_of_attendees' => $this->faker->numberBetween(1, 4),
                'special_requirements' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
            ],
            'tags' => [
                'priority_' . $this->faker->randomElement(['low', 'medium', 'high']),
                $isB2B ? 'b2b' : 'b2c',
            ],
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'held_at' => null,
            'hold_expires_at' => null,
        ]);
    }

    public function held(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'held',
            'held_at' => now(),
            'hold_expires_at' => now()->addMinutes($attributes['is_b2b'] ? 60 : 15),
        ]);
    }

    public function confirmed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'held_at' => now()->subMinutes(10),
            'hold_expires_at' => now()->addMinutes(5),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'held_at' => now()->subHours(2),
            'hold_expires_at' => now()->subHours(1),
            'completed_at' => now()->subHour(),
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now()->subHours(rand(1, 24)),
            'cancellation_reason' => $this->faker->randomElement(['client_cancelled', 'agent_cancelled']),
        ]);
    }

    public function noShow(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'no_show',
            'cancelled_at' => now()->subHours(rand(1, 24)),
            'cancellation_reason' => 'no_show',
        ]);
    }

    public function b2c(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_b2b' => false,
            'hold_expires_at' => now()->addMinutes(15),
        ]);
    }

    public function b2b(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_b2b' => true,
            'hold_expires_at' => now()->addMinutes(60),
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'held',
            'held_at' => now()->subMinutes(20),
            'hold_expires_at' => now()->subMinutes(5),
        ]);
    }
}
