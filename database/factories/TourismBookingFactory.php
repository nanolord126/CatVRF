<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Travel\Models\TourBooking;
use App\Domains\Travel\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Tourism Booking Factory
 * 
 * Factory for creating tourism booking test data.
 * Includes state methods for different booking statuses.
 */
final class TourismBookingFactory extends Factory
{
    protected $model = TourBooking::class;

    public function definition(): array
    {
        $tour = Tour::factory()->create();

        return [
            'uuid' => fake()->uuid(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'tour_id' => $tour->id,
            'user_id' => 1,
            'person_count' => fake()->numberBetween(1, 10),
            'start_date' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+2 months'),
            'total_amount' => fake()->randomFloat(2, 5000, 500000),
            'base_price' => fake()->randomFloat(2, 5000, 500000),
            'dynamic_price' => fake()->randomFloat(2, 5000, 500000),
            'discount_amount' => fake()->randomFloat(2, 0, 50000),
            'commission_rate' => fake()->randomFloat(4, 0.10, 0.15),
            'commission_amount' => fake()->randomFloat(2, 500, 75000),
            'status' => 'held',
            'biometric_token' => fake()->sha256(),
            'biometric_verified' => false,
            'hold_expires_at' => now()->addMinutes(15),
            'virtual_tour_viewed' => false,
            'virtual_tour_viewed_at' => null,
            'video_call_scheduled' => false,
            'video_call_time' => null,
            'video_call_link' => null,
            'video_call_meeting_id' => null,
            'video_call_join_url' => null,
            'payment_method' => fake()->randomElement(['card', 'wallet', 'sbp', 'split']),
            'split_payment_enabled' => fake()->boolean(20),
            'cashback_amount' => 0,
            'cancellation_reason' => null,
            'refund_amount' => 0,
            'cancelled_at' => null,
            'fraud_score' => null,
            'confirmed_at' => null,
            'correlation_id' => fake()->uuid(),
            'tags' => fake()->randomElements(['ai_personalized', 'dynamic_pricing', 'flash_sale', 'b2b'], fake()->numberBetween(0, 4)),
            'metadata' => [
                'ai_recommendations' => fake()->randomElements(['beach', 'mountain', 'cultural', 'adventure'], fake()->numberBetween(1, 3)),
                'flash_package' => fake()->boolean(10),
                'ar_enabled' => fake()->boolean(30),
                'virtual_tour_url' => fake()->boolean(50) ? fake()->url() : null,
            ],
        ];
    }

    public function held(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'held',
            'biometric_verified' => false,
            'hold_expires_at' => now()->addMinutes(15),
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'biometric_verified' => true,
            'hold_expires_at' => now()->subMinutes(5),
            'confirmed_at' => now()->subMinutes(5),
            'cashback_amount' => $attributes['total_amount'] * 0.05,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancellation_reason' => fake()->randomElement(['schedule conflict', 'found cheaper', 'emergency', 'changed plans']),
            'refund_amount' => $attributes['total_amount'] * fake()->randomFloat(2, 0.5, 1.0),
            'cancelled_at' => now()->subHours(fake()->numberBetween(1, 24)),
            'fraud_score' => fake()->randomFloat(4, 0, 1),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'biometric_verified' => true,
            'confirmed_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function noShow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'no_show',
            'biometric_verified' => true,
            'confirmed_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function b2b(): static
    {
        return $this->state(fn (array $attributes) => [
            'business_group_id' => fake()->numberBetween(1, 10),
            'commission_rate' => 0.10,
            'commission_amount' => $attributes['total_amount'] * 0.10,
            'tags' => array_merge($attributes['tags'] ?? [], ['b2b', 'corporate']),
        ]);
    }

    public function withVirtualTour(): static
    {
        return $this->state(fn (array $attributes) => [
            'virtual_tour_viewed' => true,
            'virtual_tour_viewed_at' => now()->subHours(fake()->numberBetween(1, 24)),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'virtual_tour_url' => fake()->url(),
                'ar_enabled' => true,
            ]),
        ]);
    }

    public function withVideoCall(): static
    {
        return $this->state(fn (array $attributes) => [
            'video_call_scheduled' => true,
            'video_call_time' => now()->addHours(fake()->numberBetween(2, 48)),
            'video_call_link' => fake()->url(),
            'video_call_meeting_id' => fake()->uuid(),
            'video_call_join_url' => fake()->url(),
        ]);
    }

    public function highFraudRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'fraud_score' => fake()->randomFloat(4, 0.7, 1.0),
        ]);
    }
}
