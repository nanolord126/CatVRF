<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SecurityEventFactory extends Factory
{
    protected $model = SecurityEvent::class;

    public function definition(): array
    {
        $eventTypes = [
            'auth_failure',
            'brute_force',
            'fraud_detected',
            'aml_alert',
            'suspicious_activity',
            'credential_stuffing',
            'insider_threat',
            'data_breach_attempt',
        ];

        $severities = ['info', 'warning', 'critical'];

        return [
            'event_id' => Str::uuid()->toString(),
            'user_id' => User::inRandomOrder()->first()?->id,
            'tenant_id' => User::inRandomOrder()->first()?->tenant_id,
            'event_type' => fake()->randomElement($eventTypes),
            'severity' => fake()->randomElement($severities),
            'source_ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'metadata' => [
                'details' => fake()->sentence(),
                'additional_info' => fake()->optional()->word(),
            ],
            'correlation_id' => Str::uuid()->toString(),
            'detected_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'resolved' => fake()->boolean(70), // 70% resolved
            'resolved_at' => fake()->optional(0.3, 1)->dateTimeBetween('-1 week', 'now'),
            'resolved_by' => fake()->optional()->name(),
            'resolution_notes' => fake()->optional()->sentence(),
        ];
    }

    public function critical(): self
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
            'event_type' => fake()->randomElement(['fraud_detected', 'aml_alert', 'data_breach_attempt']),
        ]);
    }

    public function unresolved(): self
    {
        return $this->state(fn (array $attributes) => [
            'resolved' => false,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolution_notes' => null,
        ]);
    }

    public function forUser(int $userId): self
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    public function forTenant(int $tenantId): self
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }
}
