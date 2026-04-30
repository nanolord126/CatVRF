<?php

declare(strict_types=1);

namespace Database\Factories\Payment;

use App\Domains\Payment\Models\EscrowHold;
use App\Domains\Payment\Models\PaymentIntent;
use Illuminate\Database\Eloquent\Factories\Factory;

class EscrowHoldFactory extends Factory
{
    protected $model = EscrowHold::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 0,
            'business_group_id' => null,
            'payment_intent_id' => PaymentIntent::factory(),
            'payment_transaction_id' => null,
            'wallet_id' => $this->faker->randomNumber(),
            'amount_kopecks' => $this->faker->numberBetween(1000, 1000000),
            'currency' => 'RUB',
            'status' => $this->faker->randomElement(['held', 'partially_released', 'released', 'canceled']),
            'release_conditions' => json_encode(['order_delivered' => true]),
            'auto_release_at' => $this->faker->dateTimeBetween('+1 day', '+7 days'),
            'released_amount_kopecks' => 0,
            'remaining_amount_kopecks' => function (array $attributes) {
                return $attributes['amount_kopecks'];
            },
            'release_reason' => null,
            'released_at' => null,
            'canceled_at' => null,
            'correlation_id' => $this->faker->uuid(),
            'metadata' => json_encode(['test' => true]),
        ];
    }

    public function held(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'held',
        ]);
    }

    public function released(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'released',
            'released_amount_kopecks' => $attributes['amount_kopecks'],
            'remaining_amount_kopecks' => 0,
            'released_at' => now(),
        ]);
    }

    public function partiallyReleased(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'partially_released',
            'released_amount_kopecks' => (int) ($attributes['amount_kopecks'] / 2),
            'remaining_amount_kopecks' => (int) ($attributes['amount_kopecks'] / 2),
        ]);
    }
}
