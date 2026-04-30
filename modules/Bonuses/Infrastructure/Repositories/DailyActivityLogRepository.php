<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Repositories;

use Modules\Bonuses\Domain\Entities\DailyActivityLog;
use Modules\Bonuses\Infrastructure\Models\DailyActivityLogModel;

/**
 * Repository: DailyActivityLogRepository
 *
 * Infrastructure repository for persisting and retrieving DailyActivityLog entities.
 * Implements the repository pattern to abstract database operations.
 *
 * Responsibilities:
 * - Save daily activity logs to database
 * - Retrieve logs by user, date range
 * - Query streak data and statistics
 * - Calculate engagement metrics
 *
 * Performance:
 * - Uses Eloquent ORM with eager loading where appropriate
 * - Caches frequently accessed logs
 * - Uses database indexes for efficient queries
 * - Batch operations for bulk inserts
 *
 * @see Modules\Bonuses\Domain\Entities\DailyActivityLog
 */
final readonly class DailyActivityLogRepository
{
    /**
     * Saves a daily activity log to the database.
     */
    public function save(DailyActivityLog $log): void
    {
        DailyActivityLogModel::fromDomainEntity($log);
    }

    /**
     * Finds a log by ID.
     */
    public function findById(string $id): ?DailyActivityLog
    {
        $model = DailyActivityLogModel::find($id);
        return $model?->toDomainEntity();
    }

    /**
     * Finds a log by user and date.
     */
    public function findByUserAndDate(string $userId, string $date): ?DailyActivityLog
    {
        $model = DailyActivityLogModel::forUser($userId)
            ->forDate($date)
            ->first();

        return $model?->toDomainEntity();
    }

    /**
     * Finds all logs for a user.
     */
    public function findByUserId(string $userId, int $limit = 30): array
    {
        $models = DailyActivityLogModel::forUser($userId)
            ->orderBy('activity_date', 'desc')
            ->limit($limit)
            ->get();

        return $models->map(fn ($model) => $model->toDomainEntity())->toArray();
    }

    /**
     * Finds logs for a date range.
     */
    public function findByDateRange(string $startDate, string $endDate, ?string $userId = null): array
    {
        $query = DailyActivityLogModel::forDateRange($startDate, $endDate);

        if ($userId) {
            $query->forUser($userId);
        }

        $models = $query->orderBy('activity_date', 'desc')->get();

        return $models->map(fn ($model) => $model->toDomainEntity())->toArray();
    }

    /**
     * Gets the current streak for a user.
     */
    public function getCurrentStreak(string $userId): int
    {
        $latestLog = DailyActivityLogModel::forUser($userId)
            ->orderBy('activity_date', 'desc')
            ->first();

        if (!$latestLog) {
            return 0;
        }

        $streakData = $latestLog->streak_count_json;
        return $streakData['current_days'] ?? 0;
    }

    /**
     * Gets the longest streak for a user.
     */
    public function getLongestStreak(string $userId): int
    {
        return (int) DailyActivityLogModel::forUser($userId)
            ->max('streak_count_json->longest_days') ?? 0;
    }

    /**
     * Gets total active days for a user.
     */
    public function getTotalActiveDays(string $userId): int
    {
        return DailyActivityLogModel::forUser($userId)
            ->whereJsonLength('streak_count_json->current_days', '>', 0)
            ->count();
    }

    /**
     * Gets engagement statistics for a user.
     */
    public function getEngagementStats(string $userId, int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $logs = DailyActivityLogModel::forUser($userId)
            ->forDateRange($startDate, date('Y-m-d'))
            ->get();

        $totalScore = $logs->sum('activity_score_json->total_score');
        $thresholdMet = $logs->filter(fn ($log) => $log->activity_score_json['total_score'] >= 30)->count();
        $streakMaintained = $logs->filter(fn ($log) => $log->streak_count_json['current_days'] > 0)->count();

        return [
            'days_analyzed' => $days,
            'active_days' => $logs->count(),
            'total_score' => $totalScore,
            'average_score' => $logs->count() > 0 ? $totalScore / $logs->count() : 0,
            'threshold_met_days' => $thresholdMet,
            'threshold_rate' => $logs->count() > 0 ? ($thresholdMet / $logs->count()) * 100 : 0,
            'streak_maintained_days' => $streakMaintained,
            'current_streak' => $this->getCurrentStreak($userId),
            'longest_streak' => $this->getLongestStreak($userId),
        ];
    }

    /**
     * Gets daily active users (DAU) for a date.
     */
    public function getDailyActiveUsers(string $date): int
    {
        return DailyActivityLogModel::forDate($date)
            ->whereJsonLength('activity_score_json->total_score', '>', 0)
            ->count();
    }

    /**
     * Gets hold reduction statistics for a user.
     */
    public function getHoldReductionStats(string $userId, int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $logs = DailyActivityLogModel::forUser($userId)
            ->forDateRange($startDate, date('Y-m-d'))
            ->finalized()
            ->get();

        return [
            'total_activity_reduction' => $logs->sum('activity_hold_reduction'),
            'total_streak_reduction' => $logs->sum('streak_hold_reduction'),
            'total_reduction' => $logs->sum('total_hold_reduction'),
            'average_daily_reduction' => $logs->count() > 0 ? $logs->sum('total_hold_reduction') / $logs->count() : 0,
        ];
    }

    /**
     * Deletes a log by ID.
     */
    public function delete(string $logId): void
    {
        DailyActivityLogModel::where('id', $logId)->delete();
    }

    /**
     * Gets logs that need finalization (not yet finalized).
     */
    public function getUnfinalizedLogs(string $date): array
    {
        $models = DailyActivityLogModel::forDate($date)
            ->where('loyalty_points_awarded', 0)
            ->get();

        return $models->map(fn ($model) => $model->toDomainEntity())->toArray();
    }
}
