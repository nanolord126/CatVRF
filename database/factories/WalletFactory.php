<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Wallet;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'uuid' => Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'current_balance' => fake()->numberBetween(100000, 1000000),
            'hold_amount' => 0,
            'cached_balance' => fake()->numberBetween(100000, 1000000),
            'meta' => [
                'currency' => 'RUB',
                'type' => 'business',
            ],
            'tags' => ['wallet:active', 'source:factory'],
        ];
    }

    public function withBalance(int $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'current_balance' => $balance,
            'cached_balance' => $balance,
        ]);
    }

    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_balance' => 0,
            'cached_balance' => 0,
        ]);
    }

    public function withHold(int $holdAmount): static
    {
        return $this->state(fn (array $attributes) => [
            'hold_amount' => $holdAmount,
        ]);
    }
}
