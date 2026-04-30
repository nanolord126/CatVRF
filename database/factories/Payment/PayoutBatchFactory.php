<?php

declare(strict_types=1);

namespace Database\Factories\Payment;

use App\Domains\Payment\Models\PayoutBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayoutBatchFactory extends Factory
{
    protected $model = PayoutBatch::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 0,
            'business_group_id' => null,
            'provider' => $this->faker->randomElement(['tinkoff', 'tochka', 'sber']),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed', 'partial']),
            'total_amount_kopecks' => $this->faker->numberBetween(100000, 10000000),
            'total_count' => $this->faker->numberBetween(1, 100),
            'processed_count' => 0,
            'failed_count' => 0,
            'currency' => 'RUB',
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 day'),
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'provider_batch_id' => $this->faker->uuid(),
            'provider_response' => json_encode(['test' => true]),
            'correlation_id' => $this->faker->uuid(),
            'metadata' => json_encode(['test' => true]),
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
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_count' => $attributes['total_count'],
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'failed_count' => $attributes['total_count'],
            'failed_at' => now(),
        ]);
    }
}
