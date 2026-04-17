<?php declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\RealEstate\Enums\BookingStatus;
use Modules\RealEstate\Models\PropertyBooking;
use Modules\RealEstate\Models\Property;

final class PropertyBookingFactory extends Factory
{
    protected $model = PropertyBooking::class;

    public function definition(): array
    {
        $property = Property::inRandomOrder()->first() ?? Property::factory()->create();

        return [
            'tenant_id' => $property->tenant_id,
            'business_group_id' => $this->faker->boolean(20) ? \App\Models\BusinessGroup::inRandomOrder()->first()?->id : null,
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            'property_id' => $property->id,
            'user_id' => \App\Models\User::inRandomOrder()->first()?->id ?? 1,
            'viewing_slot' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'amount' => $this->faker->randomFloat(2, 100000, 50000000),
            'status' => $this->faker->randomElement(BookingStatus::cases()),
            'deal_score' => [
                'overall' => $this->faker->randomFloat(4, 0, 1),
                'credit' => $this->faker->randomFloat(4, 0, 1),
                'legal' => $this->faker->randomFloat(4, 0, 1),
                'liquidity' => $this->faker->randomFloat(4, 0, 1),
                'recommended' => $this->faker->boolean(70),
            ],
            'fraud_score' => $this->faker->randomFloat(4, 0, 0.5),
            'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
            'is_b2b' => $this->faker->boolean(15),
            'hold_until' => $this->faker->dateTimeBetween('+15 minutes', '+60 minutes'),
            'face_id_verified' => $this->faker->boolean(60),
            'blockchain_verified' => $this->faker->boolean(40),
            'webrtc_room_id' => $this->faker->boolean(30) ? 'room_' . \Illuminate\Support\Str::random(8) : null,
            'original_price' => $property->price,
            'dynamic_discount' => $this->faker->randomFloat(2, 0, 500000),
            'escrow_amount' => $this->faker->boolean(50) ? $this->faker->randomFloat(2, 0, 50000000) : 0,
            'commission_split' => $this->faker->boolean(20) ? [
                'platform' => $this->faker->randomFloat(2, 0, 1000000),
                'agent' => $this->faker->randomFloat(2, 0, 500000),
                'referral' => $this->faker->randomFloat(2, 0, 300000),
                'total' => $this->faker->randomFloat(2, 0, 1500000),
            ] : null,
            'metadata' => [
                'ai_virtual_tour_enabled' => $this->faker->boolean(30),
                'ar_model_enabled' => $this->faker->boolean(20),
                'source' => $this->faker->randomElement(['web', 'mobile', 'api', 'partner']),
            ],
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::PENDING,
            'hold_until' => now()->addMinutes(30),
        ]);
    }

    public function confirmed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::CONFIRMED,
            'blockchain_verified' => true,
            'webrtc_room_id' => 'room_' . \Illuminate\Support\Str::random(8),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::COMPLETED,
            'blockchain_verified' => true,
        ]);
    }

    public function b2b(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_b2b' => true,
            'business_group_id' => \App\Models\BusinessGroup::inRandomOrder()->first()?->id,
            'commission_split' => [
                'platform' => $this->faker->randomFloat(2, 0, 1000000),
                'agent' => $this->faker->randomFloat(2, 0, 500000),
                'referral' => $this->faker->randomFloat(2, 0, 300000),
                'total' => $this->faker->randomFloat(2, 0, 1500000),
            ],
        ]);
    }

    public function withEscrow(): self
    {
        return $this->state(fn (array $attributes) => [
            'escrow_amount' => $attributes['amount'],
            'use_escrow' => true,
        ]);
    }
}
