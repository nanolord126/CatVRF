<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * DailyActivityLog - Entity for tracking daily user activity for streak and acceleration
 * 
 * Tracks daily actions that contribute to streak multipliers and hold acceleration.
 * Actions include AR-try-on, views, reviews, cross-vertical engagement, etc.
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property \Carbon\Carbon $activity_date Date of activity
 * @property int $action_count Number of actions performed
 * @property array|null $actions Detailed list of actions
 * @property int $streak_days Current streak in days
 * @property int $acceleration_days Days reduced from hold due to activity
 * @property bool $has_claimed_yield Whether user claimed float yield for this day
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class DailyActivityLog extends Model
{
    use TenantScoped;

    protected $table = 'daily_activity_logs';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'activity_date',
        'action_count',
        'actions',
        'streak_days',
        'acceleration_days',
        'has_claimed_yield',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'action_count' => 'integer',
        'actions' => 'array',
        'streak_days' => 'integer',
        'acceleration_days' => 'integer',
        'has_claimed_yield' => 'boolean',
    ];

    // Relationships

    /** @return BelongsTo<\App\Models\User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    // Scopes

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('activity_date', $date);
    }

    public function scopeForDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    public function scopeWithStreak(Builder $query, int $minDays = 1): Builder
    {
        return $query->where('streak_days', '>=', $minDays);
    }

    public function scopeHasYieldClaimed(Builder $query, bool $claimed = true): Builder
    {
        return $query->where('has_claimed_yield', $claimed);
    }

    // Domain Methods

    public function addAction(string $actionType, ?array $metadata = null): void
    {
        $actions = $this->actions ?? [];
        $actions[] = array_merge([
            'type' => $actionType,
            'timestamp' => now()->toIso8601String(),
        ], $metadata ?? []);

        $this->actions = $actions;
        $this->action_count = count($actions);
        $this->save();
    }

    public function hasMinimumActions(int $minActions = 3): bool
    {
        return $this->action_count >= $minActions;
    }

    public function getStreakMultiplier(): float
    {
        return $this->calculateStreakMultiplier($this->streak_days);
    }

    public function calculateStreakMultiplier(int $streakDays): float
    {
        if ($streakDays < 7) {
            return 1.0;
        }

        if ($streakDays < 14) {
            return 1.5;
        }

        if ($streakDays < 21) {
            return 2.0;
        }

        if ($streakDays < 30) {
            return 3.0;
        }

        return 4.0; // 30+ days
    }

    public function getAccelerationDays(): int
    {
        // 3+ actions = -1 day, 5+ actions = -2 days, 10+ actions = -3 days
        if ($this->action_count >= 10) {
            return 3;
        }

        if ($this->action_count >= 5) {
            return 2;
        }

        if ($this->action_count >= 3) {
            return 1;
        }

        return 0;
    }

    public function claimYield(): void
    {
        $this->has_claimed_yield = true;
        $this->save();
    }

    /**
     * Get or create log for today
     */
    public static function firstOrCreateForToday(int $userId, int $tenantId): self
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'activity_date' => now()->toDateString(),
            ],
            [
                'action_count' => 0,
                'actions' => [],
                'streak_days' => 0,
                'acceleration_days' => 0,
                'has_claimed_yield' => false,
            ]
        );
    }

    /**
     * Calculate current streak for a user
     */
    public static function calculateStreak(int $userId, int $tenantId): int
    {
        $streak = 0;
        $date = now();

        while (true) {
            $log = self::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->where('activity_date', $date->toDateString())
                ->first();

            if (!$log || $log->action_count < 3) {
                break;
            }

            $streak++;
            $date->subDay();
        }

        return $streak;
    }

    /**
     * Get total acceleration days for a user in a date range
     */
    public static function getTotalAccelerationDays(
        int $userId,
        int $tenantId,
        string $startDate,
        string $endDate
    ): int {
        return self::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->whereBetween('activity_date', [$startDate, $endDate])
            ->sum('acceleration_days');
    }

    /**
     * Get users with active streaks
     */
    public static function getActiveStreakUsers(int $tenantId, int $minDays = 7): \Illuminate\Support\Collection
    {
        return self::where('tenant_id', $tenantId)
            ->where('activity_date', now()->toDateString())
            ->where('streak_days', '>=', $minDays)
            ->with('user')
            ->get()
            ->sortByDesc('streak_days');
    }
}
