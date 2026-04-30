<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AccountRecoveryLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class AccountRecoveryLogFactory extends Factory
{
    protected $model = AccountRecoveryLog::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'user_id' => User::factory(),
            'tenant_id' => null,
            'method' => fake()->randomElement([
                AccountRecoveryLog::METHOD_EMAIL,
                AccountRecoveryLog::METHOD_SMS,
                AccountRecoveryLog::METHOD_BACKUP_CODE,
                AccountRecoveryLog::METHOD_AI_FACE,
            ]),
            'risk_score' => fake()->randomFloat(2, 0, 1),
            'status' => fake()->randomElement([
                AccountRecoveryLog::STATUS_INITIATED,
                AccountRecoveryLog::STATUS_VERIFIED,
                AccountRecoveryLog::STATUS_COMPLETED,
                AccountRecoveryLog::STATUS_FAILED,
            ]),
            'ip_address' => fake()->ipv4(),
            'device_fingerprint' => fake()->md5(),
            'initiated_at' => now(),
            'verified_at' => fake()->boolean(50) ? now() : null,
            'completed_at' => fake()->boolean(30) ? now() : null,
        ];
    }

    public function initiated(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountRecoveryLog::STATUS_INITIATED,
        ]);
    }

    public function verified(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountRecoveryLog::STATUS_VERIFIED,
            'verified_at' => now(),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountRecoveryLog::STATUS_COMPLETED,
            'verified_at' => now(),
            'completed_at' => now(),
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountRecoveryLog::STATUS_FAILED,
        ]);
    }

    public function highRisk(): self
    {
        return $this->state(fn (array $attributes) => [
            'risk_score' => fake()->randomFloat(2, 0.80, 1.0),
        ]);
    }
}
