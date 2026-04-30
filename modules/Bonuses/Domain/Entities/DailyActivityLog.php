<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use Modules\Bonuses\Domain\Events\DailyActivityLogged;
use Modules\Bonuses\Domain\ValueObjects\ActivityScore;
use Modules\Bonuses\Domain\ValueObjects\StreakCount;
use Ramsey\Uuid\Uuid;

/**
 * Entity: DailyActivityLog
 *
 * Represents a user's daily activity log in the CatFloat gamified daily loop.
 * Tracks daily actions, streaks, multipliers, and calculates hold period reductions.
 *
 * Daily activity mechanics:
 * - Each user has one activity log per day
 * - Log resets at midnight
 * - Activities tracked: product views, AR try-ons, reviews, cross-vertical visits, purchases, quests, shares, referrals
 * - Login is required for streak maintenance
 * - Threshold of 30+ points qualifies for hold reduction
 *
 * Streak mechanics:
 * - Streak increments when login + threshold met
 * - Streak resets to 0 if no login or threshold not met
 * - Multiplier grows exponentially with streak length
 * - Streak milestones award instant bonuses and badges
 *
 * Hold period reduction:
 * - Activity: -1 day per day of meeting threshold
 * - Streak: -3 days at 7 days, -5 days at 14 days, -8 days at 30+ days
 * - Applied to oldest locked batch first
 * - Stacks across all reduction sources
 *
 * Loyalty points:
 * - 1 activity point = 1 loyalty point
 * - Points awarded at midnight
 * - Multipliers apply: base × streak × quest × tier
 * - Points expire after 365 days
 *
 * Vertical activity:
 * - Activities tracked per vertical
 * - Cross-vertical visits earn +3 points
 * - Cross-vertical bonuses: +25% multiplier for spending in different vertical
 * - Sponsored quests focus on specific verticals
 *
 * Fraud prevention:
 * - Rate limiting on rapid actions
 * - Time-based validation
 * - IP/device fingerprinting
 * - Behavioral analysis
 *
 * Compliance:
 * - All changes logged with correlation ID
 * - PII anonymized in external logs (152-ФЗ)
 * - User can request activity history export
 *
 * @see Modules\Bonuses\Domain\ValueObjects\ActivityScore
 * @see Modules\Bonuses\Domain\ValueObjects\StreakCount
 * @see Modules\Bonuses\Domain\Events\DailyActivityLogged
 */
final readonly class DailyActivityLog
{
    /**
     * Unique identifier for this log entry.
     */
    public string $id;

    /**
     * User ID.
     */
    public string $userId;

    /**
     * Tenant ID for multi-tenancy.
     */
    public ?string $tenantId;

    /**
     * Activity date (YYYY-MM-DD).
     */
    public string $activityDate;

    /**
     * Activity score for the day.
     */
    public ActivityScore $activityScore;

    /**
     * Current streak count.
     */
    public StreakCount $streakCount;

    /**
     * Current bonus multiplier.
     */
    public float $bonusMultiplier;

    /**
     * Hold days reduction from activity.
     */
    public int $activityHoldReduction;

    /**
     * Hold days reduction from streak.
     */
    public int $streakHoldReduction;

    /**
     * Total hold days reduction.
     */
    public int $totalHoldReduction;

    /**
     * Loyalty points awarded.
     */
    public int $loyaltyPointsAwarded;

    /**
     * Timestamp when log was created.
     */
    public DateTimeImmutable $createdAt;

    /**
     * Timestamp when log was last updated.
     */
    public DateTimeImmutable $updatedAt;

    /**
     * Correlation ID for distributed tracing.
     */
    public string $correlationId;

    /**
     * Additional metadata.
     */
    public array $metadata;

    /**
     * Creates a new daily activity log.
     */
    public static function create(
        string $userId,
        string $activityDate,
        ?string $tenantId = null,
        string $correlationId = null,
        array $metadata = []
    ): self {
        $id = Uuid::uuid4()->toString();
        $now = new DateTimeImmutable();
        $correlationId = $correlationId ?? Uuid::uuid4()->toString();

        return new self(
            id: $id,
            userId: $userId,
            tenantId: $tenantId,
            activityDate: $activityDate,
            activityScore: ActivityScore::zero(),
            streakCount: StreakCount::zero(),
            bonusMultiplier: 1.0,
            activityHoldReduction: 0,
            streakHoldReduction: 0,
            totalHoldReduction: 0,
            loyaltyPointsAwarded: 0,
            createdAt: $now,
            updatedAt: $now,
            correlationId: $correlationId,
            metadata: $metadata
        );
    }

    /**
     * Private constructor.
     */
    private function __construct(
        string $id,
        string $userId,
        ?string $tenantId,
        string $activityDate,
        ActivityScore $activityScore,
        StreakCount $streakCount,
        float $bonusMultiplier,
        int $activityHoldReduction,
        int $streakHoldReduction,
        int $totalHoldReduction,
        int $loyaltyPointsAwarded,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        string $correlationId,
        array $metadata
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->activityDate = $activityDate;
        $this->activityScore = $activityScore;
        $this->streakCount = $streakCount;
        $this->bonusMultiplier = $bonusMultiplier;
        $this->activityHoldReduction = $activityHoldReduction;
        $this->streakHoldReduction = $streakHoldReduction;
        $this->totalHoldReduction = $totalHoldReduction;
        $this->loyaltyPointsAwarded = $loyaltyPointsAwarded;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->correlationId = $correlationId;
        $this->metadata = $metadata;
    }

    /**
     * Reconstructs log from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            userId: $data['user_id'],
            tenantId: $data['tenant_id'] ?? null,
            activityDate: $data['activity_date'],
            activityScore: ActivityScore::fromArray($data['activity_score']),
            streakCount: StreakCount::fromArray($data['streak_count']),
            bonusMultiplier: (float) $data['bonus_multiplier'],
            activityHoldReduction: (int) $data['activity_hold_reduction'],
            streakHoldReduction: (int) $data['streak_hold_reduction'],
            totalHoldReduction: (int) $data['total_hold_reduction'],
            loyaltyPointsAwarded: (int) $data['loyalty_points_awarded'],
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
            correlationId: $data['correlation_id'],
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Records a login.
     */
    public function recordLogin(): self
    {
        $newScore = $this->activityScore->withLogin();
        $now = new DateTimeImmutable();

        return new self(
            id: $this->id,
            userId: $this->userId,
            tenantId: $this->tenantId,
            activityDate: $this->activityDate,
            activityScore: $newScore,
            streakCount: $this->streakCount,
            bonusMultiplier: $this->bonusMultiplier,
            activityHoldReduction: $this->activityHoldReduction,
            streakHoldReduction: $this->streakHoldReduction,
            totalHoldReduction: $this->totalHoldReduction,
            loyaltyPointsAwarded: $this->loyaltyPointsAwarded,
            createdAt: $this->createdAt,
            updatedAt: $now,
            correlationId: $this->correlationId,
            metadata: $this->metadata
        );
    }

    /**
     * Records an activity.
     */
    public function recordActivity(string $activityType, int $count = 1): self
    {
        $newScore = $this->activityScore->withActivity(
            \Modules\Bonuses\Domain\Enums\ActivityType::fromString($activityType),
            $count
        );
        $now = new DateTimeImmutable();

        return new self(
            id: $this->id,
            userId: $this->userId,
            tenantId: $this->tenantId,
            activityDate: $this->activityDate,
            activityScore: $newScore,
            streakCount: $this->streakCount,
            bonusMultiplier: $this->bonusMultiplier,
            activityHoldReduction: $this->activityHoldReduction,
            streakHoldReduction: $this->streakHoldReduction,
            totalHoldReduction: $this->totalHoldReduction,
            loyaltyPointsAwarded: $this->loyaltyPointsAwarded,
            createdAt: $this->createdAt,
            updatedAt: $now,
            correlationId: $this->correlationId,
            metadata: $this->metadata
        );
    }

    /**
     * Finalizes the daily log at midnight.
     * Calculates streak, multiplier, hold reduction, and loyalty points.
     */
    public function finalize(int $baseHoldDays = 15): self
    {
        $now = new DateTimeImmutable();

        // Determine streak
        if ($this->activityScore->qualifiesForStreak()) {
            $newStreak = $this->streakCount->increment();
        } else {
            $newStreak = $this->activityScore->hasLoggedIn() ? $this->streakCount : $this->streakCount->reset();
        }

        // Calculate multipliers and reductions
        $streakMultiplier = $newStreak->getMultiplier();
        $activityReduction = $this->activityScore->getHoldDaysReduction();
        $streakReduction = $newStreak->getHoldDaysReduction($baseHoldDays);

        return new self(
            id: $this->id,
            userId: $this->userId,
            tenantId: $this->tenantId,
            activityDate: $this->activityDate,
            activityScore: $this->activityScore,
            streakCount: $newStreak,
            bonusMultiplier: $streakMultiplier,
            activityHoldReduction: $activityReduction,
            streakHoldReduction: $streakReduction,
            totalHoldReduction: $activityReduction + $streakReduction,
            loyaltyPointsAwarded: $this->activityScore->getLoyaltyPoints(),
            createdAt: $this->createdAt,
            updatedAt: $now,
            correlationId: $this->correlationId,
            metadata: $this->metadata
        );
    }

    /**
     * Dispatches DailyActivityLogged event.
     */
    public function dispatchEvent(): DailyActivityLogged
    {
        return new DailyActivityLogged(
            activityLogId: $this->id,
            userId: $this->userId,
            activityDate: $this->activityDate,
            activityScore: $this->activityScore,
            streakCount: $this->streakCount,
            bonusMultiplier: $this->bonusMultiplier,
            activityHoldReduction: $this->activityHoldReduction,
            streakHoldReduction: $this->streakHoldReduction,
            totalHoldReduction: $this->totalHoldReduction,
            loyaltyPointsAwarded: $this->loyaltyPointsAwarded,
            tenantId: $this->tenantId,
            correlationId: $this->correlationId,
            metadata: $this->metadata
        );
    }

    /**
     * Checks if log is finalized.
     */
    public function isFinalized(): bool
    {
        return $this->loyaltyPointsAwarded > 0;
    }

    /**
     * Checks if user logged in today.
     */
    public function hasLoggedIn(): bool
    {
        return $this->activityScore->hasLoggedIn();
    }

    /**
     * Checks if threshold was met.
     */
    public function meetsThreshold(): bool
    {
        return $this->activityScore->meetsThreshold();
    }

    /**
     * Checks if streak was maintained.
     */
    public function streakMaintained(): bool
    {
        return $this->streakCount->getCurrentDays() > 0;
    }

    /**
     * Converts log to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'activity_date' => $this->activityDate,
            'activity_score' => $this->activityScore->toArray(),
            'streak_count' => $this->streakCount->toArray(),
            'bonus_multiplier' => $this->bonusMultiplier,
            'activity_hold_reduction' => $this->activityHoldReduction,
            'streak_hold_reduction' => $this->streakHoldReduction,
            'total_hold_reduction' => $this->totalHoldReduction,
            'loyalty_points_awarded' => $this->loyaltyPointsAwarded,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
            'is_finalized' => $this->isFinalized(),
            'has_logged_in' => $this->hasLoggedIn(),
            'meets_threshold' => $this->meetsThreshold(),
            'streak_maintained' => $this->streakMaintained(),
        ];
    }
}
