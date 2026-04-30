<?php

declare(strict_types=1);

namespace Database\Factories\Payment;

use App\Domains\Payment\Models\PaymentIntent;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentIntentFactory extends Factory
{
    protected $model = PaymentIntent::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 0,
            'business_group_id' => null,
            'user_id' => $this->faker->randomNumber(),
            'payable_type' => null,
            'payable_id' => null,
            'amount_kopecks' => $this->faker->numberBetween(1000, 1000000),
            'currency' => 'RUB',
            'status' => $this->faker->randomElement(['pending', 'processing', 'succeeded', 'failed', 'canceled']),
            'payment_method' => $this->faker->randomElement(['card', 'sbp', 'sber_pay', 'installments']),
            'payment_method_provider' => $this->faker->randomElement(['visa', 'mastercard', 'mir']),
            'provider' => $this->faker->randomElement(['tinkoff', 'tochka', 'sber']),
            'provider_payment_intent_id' => $this->faker->uuid(),
            'capture_method' => true,
            'confirmation_url' => $this->faker->url(),
            'payment_url' => $this->faker->url(),
            'client_secret' => $this->faker->sha256,
            'requires_action' => false,
            'next_action' => null,
            'description' => $this->faker->sentence,
            'customer_email' => $this->faker->email,
            'customer_phone' => $this->faker->phoneNumber,
            'return_url' => $this->faker->url(),
            'cancel_url' => $this->faker->url(),
            'metadata' => json_encode(['test' => true]),
            'expires_at' => $this->faker->dateTimeBetween('+1 hour', '+24 hours'),
            'canceled_at' => null,
            'processing_at' => null,
            'succeeded_at' => null,
            'correlation_id' => $this->faker->uuid(),
            'fraud_score' => $this->faker->randomFloat(2, 0, 1),
            'fraud_decision' => $this->faker->randomElement(['allow', 'block', 'review']),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'processing_at' => now(),
        ]);
    }

    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'succeeded',
            'succeeded_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);
    }
}
