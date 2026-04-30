<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\IncidentResponse;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class IncidentResponseFactory extends Factory
{
    protected $model = IncidentResponse::class;

    public function definition(): array
    {
        $triggerTypes = [
            'auto_freeze_wallet',
            'auto_revoke_token',
            'auto_block_ip',
            'auto_escalate',
            'auto_block_user',
        ];

        $triggerConditions = [
            'fraud_detected',
            'credential_breach',
            'brute_force',
            'critical_event',
            'data_breach_attempt',
            'insider_threat',
            'aml_alert',
        ];

        $actions = [
            'wallet_frozen',
            'tokens_revoked',
            'ip_blocked',
            'escalated',
            'user_blocked',
            'user_blocked_and_escalated',
        ];

        $statuses = ['pending', 'executed', 'failed', 'rolled_back'];

        return [
            'response_id' => Str::uuid()->toString(),
            'security_event_id' => SecurityEvent::inRandomOrder()->first()?->id,
            'user_id' => User::inRandomOrder()->first()?->id,
            'tenant_id' => User::inRandomOrder()->first()?->tenant_id,
            'trigger_type' => fake()->randomElement($triggerTypes),
            'trigger_condition' => fake()->randomElement($triggerConditions),
            'trigger_data' => [
                'details' => fake()->sentence(),
                'confidence' => fake()->randomFloat(2, 0, 1),
            ],
            'action_taken' => fake()->randomElement($actions),
            'action_data' => [
                'result' => fake()->randomElement(['success', 'partial', 'failed']),
                'details' => fake()->optional()->sentence(),
            ],
            'status' => fake()->randomElement($statuses),
            'error_message' => fake()->optional(0.1)->sentence(),
            'executed_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'rolled_back_at' => fake()->optional(0.2)->dateTimeBetween('-1 week', 'now'),
            'auto_rollback' => fake()->boolean(30),
            'rollback_after_minutes' => fake()->optional(0.3)->numberBetween(10, 120),
            'correlation_id' => Str::uuid()->toString(),
        ];
    }

    public function executed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'executed',
            'rolled_back_at' => null,
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => fake()->sentence(),
        ]);
    }

    public function rolledBack(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'executed',
            'rolled_back_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function autoRollback(): self
    {
        return $this->state(fn (array $attributes) => [
            'auto_rollback' => true,
            'rollback_after_minutes' => fake()->numberBetween(10, 120),
        ]);
    }

    public function forTriggerType(string $triggerType): self
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => $triggerType,
        ]);
    }

    public function forUser(int $userId): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
