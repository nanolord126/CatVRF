<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Services;

use App\Domains\Bonuses\DTOs\StreakResultDto;
use App\Domains\Bonuses\Interfaces\DailyActivityRepositoryInterface;
use App\Domains\Bonuses\ValueObjects\StreakLevel;
use Illuminate\Support\Collection;

/**
 * BonusStreakService - Service for streak-related operations
 * 
 * Handles streak calculation, level determination, and streak-based rewards.
 */
final readonly class BonusStreakService
{
    public function __construct(
        private readonly DailyActivityRepositoryInterface $activityRepository,
    ) {}

    public function getStreakInfo(int $userId, int $tenantId): array
    {
        $streakDays = $this->activityRepository->getCurrentStreak($userId, $tenantId);
        $streakLevel = StreakLevel::fromDays($streakDays);

        return [
            'streak_days' => $streakDays,
            'streak_level' => $streakLevel->getLevel(),
            'multiplier' => $streakLevel->getMultiplier(),
            'color' => $streakLevel->getColor(),
            'is_premium' => $streakLevel->isPremium(),
            'acceleration_bonus' => $streakLevel->getAccelerationBonus(),
            'next_level' => $streakLevel->getNextLevel()?->getLevel(),
            'days_to_next_level' => $streakLevel->getDaysToNextLevel(),
        ];
    }

    public function getTopStreakUsers(int $tenantId, int $limit = 10): Collection
    {
        return $this->activityRepository->getTopStreakUsers($tenantId, $limit);
    }

    public function getStreakBonusMultiplier(int $userId, int $tenantId): float
    {
        $streakDays = $this->activityRepository->getCurrentStreak($userId, $tenantId);
        $streakLevel = StreakLevel::fromDays($streakDays);

        return $streakLevel->getMultiplier();
    }

    public function getAccelerationBonus(int $userId, int $tenantId): int
    {
        $streakDays = $this->activityRepository->getCurrentStreak($userId, $tenantId);
        $streakLevel = StreakLevel::fromDays($streakDays);

        return $streakLevel->getAccelerationBonus();
    }

    public function isMilestone(int $streakDays): bool
    {
        return in_array($streakDays, [7, 14, 21, 30], true);
    }

    public function getMilestoneReward(int $streakDays): float
    {
        return match ($streakDays) {
            7 => 50.0,   // Bronze -> Silver
            14 => 150.0,  // Silver -> Gold
            21 => 300.0,  // Gold -> Platinum
            30 => 500.0,  // Platinum -> Diamond
            default => 0.0,
        };
    }

    public function getStreakLevelFromDays(int $days): StreakLevel
    {
        return StreakLevel::fromDays($days);
    }

    public function getStreakStatistics(int $tenantId, string $startDate, string $endDate): array
    {
        return $this->activityRepository->getTenantStatistics($tenantId, $startDate, $endDate);
    }
}
