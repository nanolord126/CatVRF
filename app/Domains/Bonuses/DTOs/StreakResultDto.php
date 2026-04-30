<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\DTOs;

use App\Domains\Bonuses\ValueObjects\StreakLevel;

/**
 * DTO для результата обработки стрика.
 * 
 * Возвращается после обработки ежедневной активности пользователя.
 */
final readonly class StreakResultDto
{
    public function __construct(
        public int $userId,
        public int $tenantId,
        public int $streakDays,
        public StreakLevel $streakLevel,
        public float $multiplier,
        public int $accelerationDays,
        public int $actionCount,
        public string $activityDate,
        public ?string $correlationId = null,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            tenantId: (int) $data['tenant_id'],
            streakDays: (int) $data['streak_days'],
            streakLevel: $data['streak_level'] instanceof StreakLevel
                ? $data['streak_level']
                : StreakLevel::fromDays((int) $data['streak_days']),
            multiplier: (float) $data['multiplier'],
            accelerationDays: (int) $data['acceleration_days'],
            actionCount: (int) $data['action_count'],
            activityDate: $data['activity_date'] ?? now()->toDateString(),
            correlationId: $data['correlation_id'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public static function create(
        int $userId,
        int $tenantId,
        int $streakDays,
        int $actionCount,
        int $accelerationDays,
        ?string $correlationId = null,
    ): self {
        $streakLevel = StreakLevel::fromDays($streakDays);
        $multiplier = $streakLevel->getMultiplier();

        return new self(
            userId: $userId,
            tenantId: $tenantId,
            streakDays: $streakDays,
            streakLevel: $streakLevel,
            multiplier: $multiplier,
            accelerationDays: $accelerationDays,
            actionCount: $actionCount,
            activityDate: now()->toDateString(),
            correlationId: $correlationId,
            metadata: [],
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'streak_days' => $this->streakDays,
            'streak_level' => $this->streakLevel->getLevel(),
            'multiplier' => $this->multiplier,
            'acceleration_days' => $this->accelerationDays,
            'action_count' => $this->actionCount,
            'activity_date' => $this->activityDate,
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
        ];
    }

    public function toAuditContext(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'streak_days' => $this->streakDays,
            'streak_level' => $this->streakLevel->getLevel(),
            'multiplier' => $this->multiplier,
            'acceleration_days' => $this->accelerationDays,
            'action_count' => $this->actionCount,
            'activity_date' => $this->activityDate,
            'correlation_id' => $this->correlationId,
        ];
    }

    public function isPremiumLevel(): bool
    {
        return $this->streakLevel->isPremium();
    }

    public function getNextLevelDays(): ?int
    {
        return $this->streakLevel->getDaysToNextLevel();
    }

    public function validate(): bool
    {
        return $this->userId > 0
            && $this->tenantId > 0
            && $this->streakDays >= 0
            && $this->multiplier >= 1.0;
    }
}
