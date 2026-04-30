<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Events;

use DateTimeImmutable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Bonuses\Domain\Enums\VestingCurveType;

/**
 * Event: BonusLocked
 *
 * Dispatched when a bonus batch is locked with a vesting curve for CatFloat.
 * This event triggers the vesting calculation process and initiates float yield tracking.
 * The locked bonus batch will undergo daily vesting based on the configured curve type.
 *
 * Key responsibilities:
 * - Notify the vesting service to start daily vesting calculations
 * - Trigger float yield tracking for platform revenue calculation
 * - Update user's locked balance in bonus wallet
 * - Log audit trail for compliance (152-ФЗ, ФЗ-323)
 * - Send notification to user about locked bonus with unlock timeline
 *
 * Vesting curve types:
 * - LINEAR: Equal daily release (6.67% per day for 15 days)
 * - ACCELERATED: Faster release based on activity/streak (up to 10% per day)
 * - CUSTOM: Custom schedule defined in vesting_schedule JSON
 *
 * Hold period accelerators:
 * - Daily login + 3+ actions: -1 day hold
 * - Streak 7 days: -3 days hold
 * - Streak 30 days: Full unlock in 7 days instead of 15
 * - Platinum B2B / Gold CLV: Base hold 10 days instead of 15
 *
 * Event flow:
 * 1. Bonus awarded → BonusLocked event dispatched
 * 2. VestingService listens → Creates vesting schedule
 * 3. FloatYieldService listens → Starts yield calculation
 * 4. BonusWallet updated → locked_balance increased
 * 5. Notification sent → User sees locked bonus with unlock timeline
 *
 * @see Modules\Bonuses\Application\Services\VestingService
 * @see Modules\Bonuses\Application\Services\FloatYieldService
 */
final class BonusLocked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Unique identifier of the locked bonus batch.
     * This UUID is used to track the batch throughout its lifecycle.
     */
    public readonly string $batchId;

    /**
     * User ID who owns this locked bonus batch.
     * Used for wallet updates and notifications.
     */
    public readonly string $userId;

    /**
     * Tenant ID for multi-tenancy support.
     * Null if not applicable to current context.
     */
    public readonly ?string $tenantId;

    /**
     * Total bonus amount in this batch (in kopecks).
     * This is the full amount that will vest over time.
     */
    public readonly int $totalAmount;

    /**
     * Base hold period in days before accelerators.
     * Default is 15 days for B2C users, 10 days for Gold/Platinum B2B.
     */
    public readonly int $baseHoldDays;

    /**
     * Actual hold period after applying accelerators.
     * Calculated as: baseHoldDays - (activity + streak + tier accelerations).
     * Minimum 1 day to prevent instant unlock.
     */
    public readonly int $actualHoldDays;

    /**
     * Type of vesting curve for this batch.
     * Determines how the bonus unlocks over time.
     */
    public readonly VestingCurveType $vestingCurveType;

    /**
     * Timestamp when the batch was locked.
     * Used as the starting point for vesting calculations.
     */
    public readonly DateTimeImmutable $lockedAt;

    /**
     * Timestamp when the batch will be fully available.
     * Calculated based on actualHoldDays from lockedAt.
     */
    public readonly DateTimeImmutable $fullyAvailableAt;

    /**
     * Type of source that triggered the bonus award.
     * Examples: 'payment', 'referral', 'quest', 'purchase', 'review'.
     */
    public readonly ?string $sourceType;

    /**
     * ID of the source entity that triggered the bonus award.
     * Examples: order UUID, referral code, quest ID.
     */
    public readonly ?string $sourceId;

    /**
     * Vertical where the bonus was earned.
     * Examples: 'beauty', 'food', 'fashion', 'healthcare'.
     * Used for cross-vertical bonus multipliers.
     */
    public readonly ?string $vertical;

    /**
     * User tier for hold period calculation.
     * Examples: 'standard', 'gold', 'platinum'.
     * Gold/Platinum users get shorter base hold periods.
     */
    public readonly ?string $userTier;

    /**
     * Correlation ID for distributed tracing.
     * Links this event to the original bonus award operation.
     */
    public readonly string $correlationId;

    /**
     * Additional metadata for analytics and tracking.
     * May include campaign IDs, partner information, etc.
     */
    public readonly array $metadata;

    public function __construct(
        string $batchId,
        string $userId,
        int $totalAmount,
        int $baseHoldDays,
        int $actualHoldDays,
        VestingCurveType $vestingCurveType,
        DateTimeImmutable $lockedAt,
        DateTimeImmutable $fullyAvailableAt,
        ?string $sourceType = null,
        ?string $sourceId = null,
        ?string $vertical = null,
        ?string $userTier = null,
        ?string $tenantId = null,
        string $correlationId,
        array $metadata = []
    ) {
        $this->batchId = $batchId;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->totalAmount = $totalAmount;
        $this->baseHoldDays = $baseHoldDays;
        $this->actualHoldDays = $actualHoldDays;
        $this->vestingCurveType = $vestingCurveType;
        $this->lockedAt = $lockedAt;
        $this->fullyAvailableAt = $fullyAvailableAt;
        $this->sourceType = $sourceType;
        $this->sourceId = $sourceId;
        $this->vertical = $vertical;
        $this->userTier = $userTier;
        $this->correlationId = $correlationId;
        $this->metadata = $metadata;
    }

    /**
     * Gets the daily vesting percentage for this batch.
     */
    public function getDailyVestingPercentage(): float
    {
        return $this->vestingCurveType->getDailyReleasePercentage();
    }

    /**
     * Gets the expected daily unlock amount in kopecks.
     */
    public function getDailyUnlockAmount(): int
    {
        return (int) ($this->totalAmount * ($this->getDailyVestingPercentage() / 100));
    }

    /**
     * Checks if this batch has tier-based acceleration.
     */
    public function hasTierAcceleration(): bool
    {
        return $this->actualHoldDays < $this->baseHoldDays && $this->userTier !== null;
    }

    /**
     * Gets the total acceleration days applied.
     */
    public function getTotalAccelerationDays(): int
    {
        return $this->baseHoldDays - $this->actualHoldDays;
    }

    /**
     * Converts event to array for serialization/logging.
     */
    public function toArray(): array
    {
        return [
            'batch_id' => $this->batchId,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'total_amount' => $this->totalAmount,
            'base_hold_days' => $this->baseHoldDays,
            'actual_hold_days' => $this->actualHoldDays,
            'vesting_curve_type' => $this->vestingCurveType->value,
            'locked_at' => $this->lockedAt->format('Y-m-d H:i:s'),
            'fully_available_at' => $this->fullyAvailableAt->format('Y-m-d H:i:s'),
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'vertical' => $this->vertical,
            'user_tier' => $this->userTier,
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
        ];
    }
}
