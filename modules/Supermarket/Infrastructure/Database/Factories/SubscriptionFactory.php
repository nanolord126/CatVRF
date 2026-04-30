<?php

declare(strict_types=1);

namespace Modules\Supermarket\Database\Factories;

use Modules\Supermarket\Domain\Enums\SubscriptionFrequency;
use Modules\Supermarket\Domain\Enums\SubscriptionStatus;
use Modules\Supermarket\Infrastructure\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'buyer_id' => \App\Models\User::factory(),
            'seller_id' => \App\Models\Tenant::factory(),
            'status' => SubscriptionStatus::ACTIVE->value,
            'frequency' => fake()->randomElement(SubscriptionFrequency::cases())->value,
            'delivery_day' => fake()->numberBetween(1, 7),
            'next_delivery_at' => now()->addDays(fake()->numberBetween(1, 7)),
            'total_amount' => fake()->randomFloat(2, 1500, 10000) * 100, // в копейках
            'is_b2b' => fake()->boolean(20), // 20% B2B
            'paused_until' => null,
            'cancel_reason' => null,
            'cancelled_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);
    }

    public function paused(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::PAUSED->value,
            'paused_until' => now()->addDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::CANCELLED->value,
            'cancel_reason' => fake()->sentence(),
            'cancelled_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function b2b(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_b2b' => true,
        ]);
    }

    public function b2c(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_b2b' => false,
        ]);
    }

    public function weekly(): self
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => SubscriptionFrequency::WEEKLY->value,
        ]);
    }

    public function biweekly(): self
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => SubscriptionFrequency::BIWEEKLY->value,
        ]);
    }

    public function monthly(): self
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => SubscriptionFrequency::MONTHLY->value,
        ]);
    }
}
