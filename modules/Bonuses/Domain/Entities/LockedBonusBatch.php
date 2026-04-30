<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use Modules\Bonuses\Domain\Enums\BatchStatus;
use Modules\Bonuses\Domain\Enums\VestingCurveType;
use Modules\Bonuses\Domain\Events\BonusLocked;
use Modules\Bonuses\Domain\Events\BonusUnlocked;
use Modules\Bonuses\Domain\ValueObjects\HoldPeriod;
use Modules\Bonuses\Domain\ValueObjects\VestingCurve;
use Ramsey\Uuid\Uuid;

/**
 * Entity: LockedBonusBatch
 *
 * Represents a batch of locked bonuses in the CatFloat system.
 * Each batch has a vesting curve, hold period, and can be accelerated through activity, streaks, and tier.
 *
 * Lifecycle:
 * 1. Created when bonus is awarded → status: LOCKED
 * 2. Daily vesting calculations → status: VESTING
 * 3. Fully vested → status: UNLOCKED
 * 4. Sold in marketplace → status: SOLD
 *
 * Vesting mechanics:
 * - Linear: Equal daily release (6.67% per day for 15 days)
 * - Accelerated: Faster release based on activity/streak
 * - Custom: Campaign-specific schedule
 *
 * Hold period acceleration:
 * - Activity: -1 day per day of meeting 30+ point threshold
 * - Streak: -3 days at 7 days, -5 days at 14 days, -8 days at 30+ days
 * - Tier: Gold B2B -5 days, Platinum B2B -8 days
 * - Minimum hold: 1 day (except legendary status: instant)
 *
 * Float yield:
 * - Platform earns 12-18% annual on locked balance
 * - User earns 0.08-0.25% daily as micro-yield
 * - Yield calculated daily at midnight
 * - Yield stops when batch is fully unlocked
 *
 * Marketplace:
 * - Batches can be sold to other users
 * - Buyer assumes remaining vesting schedule
 * - Platform takes 10% commission on sales
 * - Instant unlock boosts available for 99 ₽ per 1000 bonuses
 *
 * Compliance:
 * - All changes logged with correlation ID
 * - Audit trail for status transitions
 * - PII anonymized in external logs (152-ФЗ)
 *
 * @see Modules\Bonuses\Domain\ValueObjects\VestingCurve
 * @see Modules\Bonuses\Domain\ValueObjects\HoldPeriod
 * @see Modules\Bonuses\Domain\Events\BonusLocked
 */
final readonly class LockedBonusBatch
{
    /**
     * Unique identifier for this batch.
     */
    public string $id;

    /**
     * User ID who owns this batch.
     */
    public string $userId;

    /**
     * Tenant ID for multi-tenancy.
     */
    public ?string $tenantId;

    /**
     * Total bonus amount in this batch (kopecks).
     */
    public int $totalAmount;

    /**
     * Amount already vested/unlocked (kopecks).
     */
    public int $unlockedAmount;

    /**
     * Current locked amount (kopecks).
     */
    public int $lockedAmount;

    /**
     * Vesting curve for this batch.
     */
    public VestingCurve $vestingCurve;

    /**
     * Hold period with acceleration factors.
     */
    public HoldPeriod $holdPeriod;

    /**
     * Current status of the batch.
     */
    public BatchStatus $status;

    /**
     * Timestamp when batch was created/locked.
     */
    public DateTimeImmutable $lockedAt;

    /**
     * Timestamp when batch will be fully available.
     */
    public DateTimeImmutable $fullyAvailableAt;

    /**
     * Source type that triggered bonus award.
     */
    public ?string $sourceType;

    /**
     * Source ID that triggered bonus award.
     */
    public ?string $sourceId;

    /**
     * Vertical where bonus was earned.
     */
    public ?string $vertical;

    /**
     * User tier for hold calculation.
     */
    public ?string $userTier;

    /**
     * Correlation ID for distributed tracing.
     */
    public string $correlationId;

    /**
     * Additional metadata.
     */
    public array $metadata;

    /**
     * Creates a new locked bonus batch.
     */
    public static function create(
        string $userId,
        int $totalAmount,
        VestingCurve $vestingCurve,
        HoldPeriod $holdPeriod,
        ?string $sourceType = null,
        ?string $sourceId = null,
        ?string $vertical = null,
        ?string $userTier = null,
        ?string $tenantId = null,
        string $correlationId = null,
        array $metadata = []
    ): self {
        $id = Uuid::uuid4()->toString();
        $now = new DateTimeImmutable();
        $correlationId = $correlationId ?? Uuid::uuid4()->toString();

        $batch = new self(
            id: $id,
            userId: $userId,
            tenantId: $tenantId,
            totalAmount: $totalAmount,
            unlockedAmount: 0,
            lockedAmount: $totalAmount,
            vestingCurve: $vestingCurve,
            holdPeriod: $holdPeriod,
            status: BatchStatus::LOCKED,
            lockedAt: $now,
            fullyAvailableAt: $holdPeriod->getEndDate(),
            sourceType: $sourceType,
            sourceId: $sourceId,
            vertical: $vertical,
            userTier: $userTier,
            correlationId: $correlationId,
            metadata: $metadata
        );

        return $batch;
    }

    /**
     * Private constructor.
     */
    private function __construct(
        string $id,
        string $userId,
        ?string $tenantId,
        int $totalAmount,
        int $unlockedAmount,
        int $lockedAmount,
        VestingCurve $vestingCurve,
        HoldPeriod $holdPeriod,
        BatchStatus $status,
        DateTimeImmutable $lockedAt,
        DateTimeImmutable $fullyAvailableAt,
        ?string $sourceType,
        ?string $sourceId,
        ?string $vertical,
        ?string $userTier,
        string $correlationId,
        array $metadata
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->totalAmount = $totalAmount;
        $this->unlockedAmount = $unlockedAmount;
        $this->lockedAmount = $lockedAmount;
        $this->vestingCurve = $vestingCurve;
        $this->holdPeriod = $holdPeriod;
        $this->status = $status;
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
     * Reconstructs batch from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            userId: $data['user_id'],
            tenantId: $data['tenant_id'] ?? null,
            totalAmount: (int) $data['total_amount'],
            unlockedAmount: (int) $data['unlocked_amount'],
            lockedAmount: (int) $data['locked_amount'],
            vestingCurve: VestingCurve::fromArray($data['vesting_curve']),
            holdPeriod: HoldPeriod::fromArray($data['hold_period']),
            status: BatchStatus::fromString($data['status']),
            lockedAt: new DateTimeImmutable($data['locked_at']),
            fullyAvailableAt: new DateTimeImmutable($data['fully_available_at']),
            sourceType: $data['source_type'] ?? null,
            sourceId: $data['source_id'] ?? null,
            vertical: $data['vertical'] ?? null,
            userTier: $data['user_tier'] ?? null,
            correlationId: $data['correlation_id'],
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Dispatches BonusLocked event.
     */
    public function dispatchLockedEvent(): BonusLocked
    {
        return new BonusLocked(
            batchId: $this->id,
            userId: $this->userId,
            totalAmount: $this->totalAmount,
            baseHoldDays: $this->holdPeriod->getBaseDays(),
            actualHoldDays: $this->holdPeriod->getActualHoldDays(),
            vestingCurveType: $this->vestingCurve->getType(),
            lockedAt: $this->lockedAt,
            fullyAvailableAt: $this->fullyAvailableAt,
            sourceType: $this->sourceType,
            sourceId: $this->sourceId,
            vertical: $this->vertical,
            userTier: $this->userTier,
            tenantId: $this->tenantId,
            correlationId: $this->correlationId,
            metadata: $this->metadata
        );
    }

    /**
     * Processes daily vesting.
     * Returns the amount to unlock today.
     */
    public function processDailyVesting(DateTimeImmutable $now): int
    {
        if ($this->isFullyUnlocked()) {
            return 0;
        }

        $daysElapsed = $this->holdPeriod->getDaysElapsed($now);
        $unlockAmount = $this->vestingCurve->getUnlockAmountForDay($this->totalAmount, $daysElapsed);

        return min($unlockAmount, $this->lockedAmount);
    }

    /**
     * Applies vesting to the batch.
     */
    public function applyVesting(int $amount, DateTimeImmutable $now): self
    {
        if ($amount > $this->lockedAmount) {
            throw new DomainException('Cannot vest more than locked amount');
        }

        $newUnlocked = $this->unlockedAmount + $amount;
        $newLocked = $this->lockedAmount - $amount;
        $newStatus = $newLocked === 0 ? BatchStatus::UNLOCKED : BatchStatus::VESTING;

        return new self(
            id: $this->id,
            userId: $this->userId,
            tenantId: $this->tenantId,
            totalAmount: $this->totalAmount,
            unlockedAmount: $newUnlocked,
            lockedAmount: $newLocked,
            vestingCurve: $this->vestingCurve,
            holdPeriod: $this->holdPeriod,
            status: $newStatus,
            lockedAt: $this->lockedAt,
            fullyAvailableAt: $this->fullyAvailableAt,
            sourceType: $this->sourceType,
            sourceId: $this->sourceId,
            vertical: $this->vertical,
            userTier: $this->userTier,
            correlationId: $this->correlationId,
            metadata: $this->metadata
        );
    }

    /**
     * Dispatches BonusUnlocked event when fully vested.
     */
    public function dispatchUnlockedEvent(int $platformYield, int $userYield): BonusUnlocked
    {
        $daysElapsed = $this->holdPeriod->getDaysElapsed(new DateTimeImmutable());

        return new BonusUnlocked(
            batchId: $this->id,
            userId: $this->userId,
            unlockedAmount: $this->lockedAmount,
            totalAmount: $this->totalAmount,
            previouslyUnlockedAmount: $this->unlockedAmount,
            unlockedAt: new DateTimeImmutable(),
            daysElapsed: $daysElapsed,
            baseHoldDays: $this->holdPeriod->getBaseDays(),
            actualHoldDays: $this->holdPeriod->getActualHoldDays(),
            platformYield: $platformYield,
            userYield: $userYield,
            tenantId: $this->tenantId,
            correlationId: $this->correlationId,
            metadata: $this->metadata
        );
    }

    /**
     * Marks batch as sold in marketplace.
     */
    public function markAsSold(string $newUserId): self
    {
        return new self(
            id: $this->id,
            userId: $newUserId,
            tenantId: $this->tenantId,
            totalAmount: $this->totalAmount,
            unlockedAmount: $this->unlockedAmount,
            lockedAmount: $this->lockedAmount,
            vestingCurve: $this->vestingCurve,
            holdPeriod: $this->holdPeriod,
            status: BatchStatus::SOLD,
            lockedAt: $this->lockedAt,
            fullyAvailableAt: $this->fullyAvailableAt,
            sourceType: $this->sourceType,
            sourceId: $this->sourceId,
            vertical: $this->vertical,
            userTier: $this->userTier,
            correlationId: $this->correlationId,
            metadata: array_merge($this->metadata, ['sold_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s')])
        );
    }

    /**
     * Checks if batch is fully unlocked.
     */
    public function isFullyUnlocked(): bool
    {
        return $this->lockedAmount === 0 || $this->status === BatchStatus::UNLOCKED;
    }

    /**
     * Checks if hold period has ended.
     */
    public function hasHoldPeriodEnded(DateTimeImmutable $now): bool
    {
        return $this->holdPeriod->hasEnded($now);
    }

    /**
     * Gets vesting progress percentage.
     */
    public function getVestingProgress(): float
    {
        if ($this->totalAmount === 0) {
            return 0.0;
        }
        return ($this->unlockedAmount / $this->totalAmount) * 100;
    }

    /**
     * Gets the days remaining until full unlock.
     */
    public function getDaysRemaining(DateTimeImmutable $now): int
    {
        return $this->holdPeriod->getRemainingDays($now);
    }

    /**
     * Converts batch to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'total_amount' => $this->totalAmount,
            'unlocked_amount' => $this->unlockedAmount,
            'locked_amount' => $this->lockedAmount,
            'vesting_curve' => $this->vestingCurve->toArray(),
            'hold_period' => $this->holdPeriod->toArray(),
            'status' => $this->status->value,
            'locked_at' => $this->lockedAt->format('Y-m-d H:i:s'),
            'fully_available_at' => $this->fullyAvailableAt->format('Y-m-d H:i:s'),
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'vertical' => $this->vertical,
            'user_tier' => $this->userTier,
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
            'vesting_progress' => $this->getVestingProgress(),
            'is_fully_unlocked' => $this->isFullyUnlocked(),
        ];
    }
}
