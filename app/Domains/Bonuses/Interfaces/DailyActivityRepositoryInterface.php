<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Interfaces;

use App\Domains\Bonuses\Models\DailyActivityLog;
use Illuminate\Support\Collection;

/**
 * DailyActivityRepositoryInterface - Repository interface for daily activity logs
 * 
 * Defines contract for activity tracking operations following DDD principles.
 * Implementation in Infrastructure layer.
 */
interface DailyActivityRepositoryInterface
{
    /**
     * Get or create log for today
     */
    public function firstOrCreateForToday(int $userId, int $tenantId): DailyActivityLog;

    /**
     * Find log by user and date
     */
    public function findByUserAndDate(int $userId, int $tenantId, string $date): ?DailyActivityLog;

    /**
     * Get logs for date range
     */
    public function getLogsForDateRange(int $userId, int $tenantId, string $startDate, string $endDate): Collection;

    /**
     * Get current streak for a user
     */
    public function getCurrentStreak(int $userId, int $tenantId): int;

    /**
     * Calculate streak from logs
     */
    public function calculateStreak(int $userId, int $tenantId): int;

    /**
     * Get total acceleration days for date range
     */
    public function getTotalAccelerationDays(
        int $userId,
        int $tenantId,
        string $startDate,
        string $endDate
    ): int;

    /**
     * Get users with active streaks
     */
    public function getActiveStreakUsers(int $tenantId, int $minDays = 7): Collection;

    /**
     * Update log
     */
    public function update(DailyActivityLog $log): bool;

    /**
     * Add action to log
     */
    public function addAction(DailyActivityLog $log, string $actionType, ?array $metadata = null): bool;

    /**
     * Mark yield as claimed
     */
    public function markYieldClaimed(DailyActivityLog $log): bool;

    /**
     * Get activity statistics for tenant
     */
    public function getTenantStatistics(int $tenantId, string $startDate, string $endDate): array;

    /**
     * Get top users by streak
     */
    public function getTopStreakUsers(int $tenantId, int $limit = 10): Collection;
}
