<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Infrastructure\Repositories;

use App\Domains\Bonuses\Interfaces\DailyActivityRepositoryInterface;
use App\Domains\Bonuses\Models\DailyActivityLog;
use Illuminate\Support\Collection;

/**
 * DailyActivityRepository - Infrastructure implementation of daily activity repository
 * 
 * Implements the domain interface using Eloquent ORM.
 */
final readonly class DailyActivityRepository implements DailyActivityRepositoryInterface
{
    public function firstOrCreateForToday(int $userId, int $tenantId): DailyActivityLog
    {
        return DailyActivityLog::firstOrCreateForToday($userId, $tenantId);
    }

    public function findByUserAndDate(int $userId, int $tenantId, string $date): ?DailyActivityLog
    {
        return DailyActivityLog::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('activity_date', $date)
            ->first();
    }

    public function getLogsForDateRange(int $userId, int $tenantId, string $startDate, string $endDate): Collection
    {
        return DailyActivityLog::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->forDateRange($startDate, $endDate)
            ->get();
    }

    public function getCurrentStreak(int $userId, int $tenantId): int
    {
        return DailyActivityLog::calculateStreak($userId, $tenantId);
    }

    public function calculateStreak(int $userId, int $tenantId): int
    {
        return DailyActivityLog::calculateStreak($userId, $tenantId);
    }

    public function getTotalAccelerationDays(
        int $userId,
        int $tenantId,
        string $startDate,
        string $endDate
    ): int {
        return DailyActivityLog::getTotalAccelerationDays($userId, $tenantId, $startDate, $endDate);
    }

    public function getActiveStreakUsers(int $tenantId, int $minDays = 7): Collection
    {
        return DailyActivityLog::getActiveStreakUsers($tenantId, $minDays);
    }

    public function update(DailyActivityLog $log): bool
    {
        return $log->save();
    }

    public function addAction(DailyActivityLog $log, string $actionType, ?array $metadata = null): bool
    {
        $log->addAction($actionType, $metadata);
        return true;
    }

    public function markYieldClaimed(DailyActivityLog $log): bool
    {
        $log->claimYield();
        return true;
    }

    public function getTenantStatistics(int $tenantId, string $startDate, string $endDate): array
    {
        $totalLogs = DailyActivityLog::where('tenant_id', $tenantId)
            ->forDateRange($startDate, $endDate)
            ->count();

        $totalActions = DailyActivityLog::where('tenant_id', $tenantId)
            ->forDateRange($startDate, $endDate)
            ->sum('action_count');

        $totalAcceleration = DailyActivityLog::where('tenant_id', $tenantId)
            ->forDateRange($startDate, $endDate)
            ->sum('acceleration_days');

        $activeStreakUsers = DailyActivityLog::getActiveStreakUsers($tenantId, 7)->count();

        return [
            'total_logs' => $totalLogs,
            'total_actions' => $totalActions,
            'total_acceleration_days' => $totalAcceleration,
            'active_streak_users' => $activeStreakUsers,
        ];
    }

    public function getTopStreakUsers(int $tenantId, int $limit = 10): Collection
    {
        return DailyActivityLog::where('tenant_id', $tenantId)
            ->where('activity_date', now()->toDateString())
            ->withStreak(7)
            ->orderByDesc('streak_days')
            ->with('user')
            ->limit($limit)
            ->get();
    }
}
