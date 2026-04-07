<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'idempotency_key' => Str::uuid(),
            'provider_code' => fake()->randomElement(['tinkoff', 'tochka', 'sber']),
            'provider_payment_id' => 'pay_' . Str::random(20),
            'amount' => fake()->numberBetween(10000, 1000000),
            'currency' => 'RUB',
            'status' => 'pending',
            'payment_method' => fake()->randomElement(['credit_card', 'debit_card', 'bank_transfer', 'sbp']),
            'hold' => false,
            'hold_amount' => 0,
            'captured_at' => null,
            'refunded_at' => null,
            'fraud_score' => 0.0,
            'ml_fraud_version' => 'v1',
            'correlation_id' => (string) Str::uuid(),
            'meta' => [
                'order_id' => 'ORD-' . Str::random(10),
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
            ],
            'tags' => ['payment:test', 'source:factory'],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'captured',
            'captured_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'captured_at' => now(),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'captured_at' => now(),
            'refunded_at' => now(),
        ]);
    }

    public function onHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'authorized',
            'hold' => true,
            'hold_amount' => $attributes['amount'],
        ]);
    }
}
