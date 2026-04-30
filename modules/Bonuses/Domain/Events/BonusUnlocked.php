<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Events;

use DateTimeImmutable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: BonusUnlocked
 *
 * Dispatched when a locked bonus batch completes its vesting period and becomes fully available.
 * This event marks the end of the vesting process and triggers wallet balance updates.
 *
 * Key responsibilities:
 * - Update user's available balance in bonus wallet (add unlocked amount)
 * - Decrease user's locked balance in bonus wallet
 * - Stop float yield tracking for this batch
 * - Send notification to user about unlocked bonus
 * - Log audit trail for compliance (152-ФЗ, ФЗ-323)
 * - Trigger cross-vertical bonus multiplier if applicable
 * - Update analytics for vesting completion rate
 *
 * Vesting completion scenarios:
 * 1. Linear vesting: All 15 days elapsed → 100% unlocked
 * 2. Accelerated vesting: Faster completion due to activity/streak
 * 3. Tier acceleration: Gold/Platinum users unlock faster
 * 4. Instant unlock: User paid for instant unlock boost (99 ₽ for 1000 bonuses)
 * 5. Marketplace sale: Batch sold to another user → transferred
 *
 * Float yield impact:
 * - Platform revenue from this batch stops accumulating
 * - User's share of yield is already credited daily
 * - Final yield calculation is logged for reporting
 *
 * Cross-vertical bonus multipliers:
 * - Bonuses earned in Food give +25% if spent in Beauty within 48h
 * - Bonuses earned in Fashion give +20% if spent in Auto within 48h
 * - Multiplier applied when user spends unlocked bonuses
 *
 * Event flow:
 * 1. Daily vesting job detects completion → BonusUnlocked event dispatched
 * 2. WalletIntegrationService listens → Updates available/locked balances
 * 3. FloatYieldService listens → Stops yield tracking, logs final revenue
 * 4. NotificationService listens → Sends push notification to user
 * 5. AnalyticsService listens → Records vesting completion metrics
 *
 * @see Modules\Bonuses\Infrastructure\Services\WalletIntegrationService
 * @see Modules\Bonuses\Application\Services\FloatYieldService
 */
final class BonusUnlocked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Unique identifier of the unlocked bonus batch.
     */
    public readonly string $batchId;

    /**
     * User ID who owns this bonus batch.
     */
    public readonly string $userId;

    /**
     * Tenant ID for multi-tenancy support.
     */
    public readonly ?string $tenantId;

    /**
     * Total amount that was unlocked (in kopecks).
     * This is the remaining locked amount at vesting completion.
     */
    public readonly int $unlockedAmount;

    /**
     * Total amount that was originally in this batch (in kopecks).
     */
    public readonly int $totalAmount;

    /**
     * Amount that was already unlocked through vesting (in kopecks).
     * This is totalAmount - unlockedAmount.
     */
    public readonly int $previouslyUnlockedAmount;

    /**
     * Timestamp when the batch was fully unlocked.
     */
    public readonly DateTimeImmutable $unlockedAt;

    /**
     * Number of days elapsed since locking.
     * May be less than baseHoldDays if accelerators were applied.
     */
    public readonly int $daysElapsed;

    /**
     * Original base hold period in days.
     */
    public readonly int $baseHoldDays;

    /**
     * Actual hold period after accelerators.
     */
    public readonly int $actualHoldDays;

    /**
     * Total float yield generated for platform (in kopecks).
     */
    public readonly int $platformYield;

    /**
     * Total float yield credited to user (in kopecks).
     */
    public readonly int $userYield;

    /**
     * Correlation ID for distributed tracing.
     */
    public readonly string $correlationId;

    /**
     * Additional metadata for analytics.
     */
    public readonly array $metadata;

    public function __construct(
        string $batchId,
        string $userId,
        int $unlockedAmount,
        int $totalAmount,
        int $previouslyUnlockedAmount,
        DateTimeImmutable $unlockedAt,
        int $daysElapsed,
        int $baseHoldDays,
        int $actualHoldDays,
        int $platformYield,
        int $userYield,
        ?string $tenantId = null,
        string $correlationId,
        array $metadata = []
    ) {
        $this->batchId = $batchId;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->unlockedAmount = $unlockedAmount;
        $this->totalAmount = $totalAmount;
        $this->previouslyUnlockedAmount = $previouslyUnlockedAmount;
        $this->unlockedAt = $unlockedAt;
        $this->daysElapsed = $daysElapsed;
        $this->baseHoldDays = $baseHoldDays;
        $this->actualHoldDays = $actualHoldDays;
        $this->platformYield = $platformYield;
        $this->userYield = $userYield;
        $this->correlationId = $correlationId;
        $this->metadata = $metadata;
    }

    /**
     * Gets the total acceleration days applied.
     */
    public function getTotalAccelerationDays(): int
    {
        return $this->baseHoldDays - $this->actualHoldDays;
    }

    /**
     * Gets the acceleration percentage.
     */
    public function getAccelerationPercentage(): float
    {
        if ($this->baseHoldDays === 0) {
            return 0.0;
        }
        return ($this->getTotalAccelerationDays() / $this->baseHoldDays) * 100;
    }

    /**
     * Gets the total yield generated (platform + user).
     */
    public function getTotalYield(): int
    {
        return $this->platformYield + $this->userYield;
    }

    /**
     * Gets the yield as percentage of total amount.
     */
    public function getYieldPercentage(): float
    {
        if ($this->totalAmount === 0) {
            return 0.0;
        }
        return ($this->getTotalYield() / $this->totalAmount) * 100;
    }

    /**
     * Checks if this unlock was accelerated.
     */
    public function isAccelerated(): bool
    {
        return $this->daysElapsed < $this->baseHoldDays;
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
            'unlocked_amount' => $this->unlockedAmount,
            'total_amount' => $this->totalAmount,
            'previously_unlocked_amount' => $this->previouslyUnlockedAmount,
            'unlocked_at' => $this->unlockedAt->format('Y-m-d H:i:s'),
            'days_elapsed' => $this->daysElapsed,
            'base_hold_days' => $this->baseHoldDays,
            'actual_hold_days' => $this->actualHoldDays,
            'total_acceleration_days' => $this->getTotalAccelerationDays(),
            'acceleration_percentage' => $this->getAccelerationPercentage(),
            'platform_yield' => $this->platformYield,
            'user_yield' => $this->userYield,
            'total_yield' => $this->getTotalYield(),
            'yield_percentage' => $this->getYieldPercentage(),
            'is_accelerated' => $this->isAccelerated(),
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
        ];
    }
}
