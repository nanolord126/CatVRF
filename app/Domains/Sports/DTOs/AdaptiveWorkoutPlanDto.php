<?php

declare(strict_types=1);

namespace App\Domains\Sports\DTOs;

final readonly class AdaptiveWorkoutPlanDto
{
    public function __construct(
        public int $userId,
        public int $tenantId,
        public ?int $businessGroupId,
        public string $correlationId,
        public string $fitnessLevel,
        public array $goals,
        public array $limitations,
        public string $sportType,
        public int $weeklyFrequency,
        public int $sessionDurationMinutes,
        public array $availableEquipment,
        public ?string $idempotencyKey = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            tenantId: $data['tenant_id'],
            businessGroupId: $data['business_group_id'] ?? null,
            correlationId: $data['correlation_id'],
            fitnessLevel: $data['fitness_level'],
            goals: $data['goals'] ?? [],
            limitations: $data['limitations'] ?? [],
            sportType: $data['sport_type'],
            weeklyFrequency: $data['weekly_frequency'] ?? 3,
            sessionDurationMinutes: $data['session_duration_minutes'] ?? 60,
            availableEquipment: $data['available_equipment'] ?? [],
            idempotencyKey: $data['idempotency_key'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'correlation_id' => $this->correlationId,
            'fitness_level' => $this->fitnessLevel,
            'goals' => $this->goals,
            'limitations' => $this->limitations,
            'sport_type' => $this->sportType,
            'weekly_frequency' => $this->weeklyFrequency,
            'session_duration_minutes' => $this->sessionDurationMinutes,
            'available_equipment' => $this->availableEquipment,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }

    public static function fromJson(string $json): self
    {
        return self::from(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
