<?php

declare(strict_types=1);

namespace Modules\Supermarket\Database\Factories;

use Modules\Supermarket\Infrastructure\Models\SubscriptionPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPaymentFactory extends Factory
{
    protected $model = SubscriptionPayment::class;

    public function definition(): array
    {
        return [
            'subscription_id' => \Modules\Supermarket\Infrastructure\Models\Subscription::factory(),
            'amount' => fake()->randomFloat(2, 1500, 10000) * 100, // в копейках
            'payment_method' => fake()->randomElement(['card', 'invoice', 'sbp']),
            'status' => fake()->randomElement(['pending', 'success', 'failed', 'refunded']),
            'external_payment_id' => fake()->uuid(),
            'payment_gateway' => fake()->randomElement(['tinkoff', 'tochka', 'sber']),
            'paid_at' => fake()->boolean(70) ? now()->subHours(fake()->numberBetween(1, 72)) : null,
            'failed_at' => fake()->boolean(10) ? now()->subHours(fake()->numberBetween(1, 24)) : null,
            'refund_amount' => fake()->boolean(5) ? fake()->randomFloat(2, 100, 5000) * 100 : null,
            'refund_reason' => null,
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
            'failed_at' => null,
        ]);
    }

    public function success(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'paid_at' => now()->subHours(fake()->numberBetween(1, 72)),
            'failed_at' => null,
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'paid_at' => null,
            'failed_at' => now()->subHours(fake()->numberBetween(1, 24)),
        ]);
    }

    public function refunded(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'paid_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'refund_amount' => $attributes['amount'],
            'refund_reason' => fake()->sentence(),
        ]);
    }

    public function card(): self
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'card',
            'payment_gateway' => fake()->randomElement(['tinkoff', 'sber']),
        ]);
    }

    public function invoice(): self
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'invoice',
            'payment_gateway' => 'tochka',
        ]);
    }

    public function sbp(): self
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'sbp',
            'payment_gateway' => fake()->randomElement(['tinkoff', 'sber']),
        ]);
    }
}
