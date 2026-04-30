<?php

declare(strict_types=1);

namespace Database\Factories\Payment;

use App\Domains\Payment\Models\RecurringSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringSubscriptionFactory extends Factory
{
    protected $model = RecurringSubscription::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 0,
            'business_group_id' => null,
            'user_id' => $this->faker->randomNumber(),
            'product_id' => $this->faker->randomNumber(),
            'product_type' => 'subscription',
            'amount_kopecks' => $this->faker->numberBetween(1000, 50000),
            'currency' => 'RUB',
            'status' => $this->faker->randomElement(['incomplete', 'trialing', 'active', 'past_due', 'canceled', 'unpaid']),
            'interval' => $this->faker->randomElement(['day', 'week', 'month', 'year']),
            'interval_count' => 1,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'trial_start' => null,
            'trial_end' => null,
            'cancel_at_period_end' => false,
            'canceled_at' => null,
            'ended_at' => null,
            'payment_method_id' => null,
            'provider' => $this->faker->randomElement(['tinkoff', 'tochka', 'sber']),
            'provider_subscription_id' => $this->faker->uuid(),
            'last_payment_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'next_payment_at' => now()->addMonth(),
            'failed_payment_count' => $this->faker->numberBetween(0, 3),
            'max_retries' => 3,
            'correlation_id' => $this->faker->uuid(),
            'metadata' => json_encode(['test' => true]),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function trialing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'trialing',
            'trial_start' => now(),
            'trial_end' => now()->addDays(14),
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'past_due',
            'failed_payment_count' => 3,
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'canceled',
            'canceled_at' => now(),
            'ended_at' => now(),
        ]);
    }
}
