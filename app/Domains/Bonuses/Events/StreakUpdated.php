<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Events;

use App\Domains\Bonuses\DTOs\StreakResultDto;
use App\Domains\Bonuses\ValueObjects\StreakLevel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * StreakUpdated - Event dispatched when user streak is updated
 * 
 * Triggers listeners for:
 * - Notification sending (milestone achievements)
 * - BigData tracking
 * - Gamification rewards
 * - Analytics
 */
final class StreakUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly StreakResultDto $dto,
        public readonly ?StreakLevel $previousLevel = null,
    ) {}

    public function getUserId(): int
    {
        return $this->dto->userId;
    }

    public function getTenantId(): int
    {
        return $this->dto->tenantId;
    }

    public function getStreakDays(): int
    {
        return $this->dto->streakDays;
    }

    public function getStreakLevel(): StreakLevel
    {
        return $this->dto->streakLevel;
    }

    public function getMultiplier(): float
    {
        return $this->dto->multiplier;
    }

    public function getAccelerationDays(): int
    {
        return $this->dto->accelerationDays;
    }

    public function getActionCount(): int
    {
        return $this->dto->actionCount;
    }

    public function isLevelUp(): bool
    {
        if ($this->previousLevel === null) {
            return false;
        }

        return !$this->previousLevel->equals($this->dto->streakLevel);
    }

    public function getPreviousLevel(): ?StreakLevel
    {
        return $this->previousLevel;
    }

    public function isMilestone(): bool
    {
        // Milestones: 7, 14, 21, 30 days
        return in_array($this->dto->streakDays, [7, 14, 21, 30], true);
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->dto->userId,
            'tenant_id' => $this->dto->tenantId,
            'streak_days' => $this->dto->streakDays,
            'streak_level' => $this->dto->streakLevel->getLevel(),
            'multiplier' => $this->dto->multiplier,
            'acceleration_days' => $this->dto->accelerationDays,
            'action_count' => $this->dto->actionCount,
            'activity_date' => $this->dto->activityDate,
            'previous_level' => $this->previousLevel?->getLevel(),
            'is_level_up' => $this->isLevelUp(),
            'is_milestone' => $this->isMilestone(),
            'correlation_id' => $this->dto->correlationId,
        ];
    }
}
