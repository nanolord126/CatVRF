<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\Services;

use DateTimeImmutable;
use DomainException;
use Illuminate\Events\Dispatcher;
use Modules\Bonuses\Domain\Entities\DailyActivityLog;
use Modules\Bonuses\Domain\Entities\LockedBonusBatch;
use Modules\Bonuses\Domain\Enums\BatchStatus;
use Modules\Bonuses\Domain\Enums\QuestStatus;
use Modules\Bonuses\Domain\Enums\VestingCurveType;
use Modules\Bonuses\Domain\Events\BonusLocked;
use Modules\Bonuses\Domain\Events\DailyActivityLogged;
use Modules\Bonuses\Domain\Interfaces\QuestEngineInterface;
use Modules\Bonuses\Domain\Interfaces\VestingCalculatorInterface;
use Modules\Bonuses\Domain\ValueObjects\HoldPeriod;
use Modules\Bonuses\Domain\ValueObjects\VestingCurve;
use Modules\Bonuses\Infrastructure\Repositories\DailyActivityLogRepository;
use Modules\Bonuses\Infrastructure\Repositories\LockedBonusBatchRepository;
use Ramsey\Uuid\Uuid;

/**
 * Service: CatFloatService
 *
 * Main application service for the CatFloat gamified bonus system.
 * Orchestrates bonus awarding, vesting, activity tracking, and quest management.
 *
 * Core responsibilities:
 * - Award locked bonus batches with vesting curves
 * - Track daily user activity and streaks
 * - Calculate and apply vesting schedules
 * - Manage daily quests and rewards
 * - Apply hold period accelerations
 *
 * No facades - all dependencies injected via constructor.
 *
 * @see Modules\Bonuses\Domain\Entities\LockedBonusBatch
 * @see Modules\Bonuses\Domain\Entities\DailyActivityLog
 */
final readonly class CatFloatService
{
    public function __construct(
        private LockedBonusBatchRepository $batchRepository,
        private DailyActivityLogRepository $activityRepository,
        private VestingCalculatorInterface $vestingCalculator,
        private QuestEngineInterface $questEngine,
        private Dispatcher $eventDispatcher
    ) {
    }

    /**
     * Awards a locked bonus batch to a user.
     */
    public function awardLockedBonus(
        string $userId,
        int $amount,
        VestingCurveType $curveType,
        ?string $sourceType = null,
        ?string $sourceId = null,
        ?string $vertical = null,
        ?string $userTier = null,
        ?string $tenantId = null
    ): LockedBonusBatch {
        $correlationId = Uuid::uuid4()->toString();

        // Create vesting curve based on type
        $vestingCurve = match ($curveType) {
            VestingCurveType::LINEAR => VestingCurve::linear(15),
            VestingCurveType::ACCELERATED => VestingCurve::accelerated(15),
            VestingCurveType::CUSTOM => VestingCurve::linear(15), // Default to linear for custom
        };

        // Create hold period based on user tier
        $holdPeriod = match ($userTier) {
            'platinum' => HoldPeriod::forPlatinumB2B(new DateTimeImmutable()),
            'gold' => HoldPeriod::forGoldB2B(new DateTimeImmutable()),
            default => HoldPeriod::forB2C(new DateTimeImmutable()),
        };

        $batch = LockedBonusBatch::create(
            userId: $userId,
            totalAmount: $amount,
            vestingCurve: $vestingCurve,
            holdPeriod: $holdPeriod,
            sourceType: $sourceType,
            sourceId: $sourceId,
            vertical: $vertical,
            userTier: $userTier,
            tenantId: $tenantId,
            correlationId: $correlationId
        );

        $this->batchRepository->save($batch);

        $event = $batch->dispatchLockedEvent();
        $this->eventDispatcher->dispatch($event);

        return $batch;
    }

    /**
     * Records user login for the day.
     */
    public function recordLogin(string $userId, string $date, ?string $tenantId = null): DailyActivityLog
    {
        $log = $this->activityRepository->findByUserAndDate($userId, $date);

        if ($log === null) {
            $log = DailyActivityLog::create(
                userId: $userId,
                activityDate: $date,
                tenantId: $tenantId
            );
        }

        $log = $log->recordLogin();
        $this->activityRepository->save($log);

        return $log;
    }

    /**
     * Records user activity for the day.
     */
    public function recordActivity(
        string $userId,
        string $date,
        string $activityType,
        int $count = 1,
        ?string $tenantId = null
    ): DailyActivityLog {
        $log = $this->activityRepository->findByUserAndDate($userId, $date);

        if ($log === null) {
            $log = DailyActivityLog::create(
                userId: $userId,
                activityDate: $date,
                tenantId: $tenantId
            );
        }

        $log = $log->recordActivity($activityType, $count);
        $this->activityRepository->save($log);

        return $log;
    }

    /**
     * Finalizes daily activity log and awards rewards.
     */
    public function finalizeDailyActivity(string $userId, string $date, int $baseHoldDays = 15): DailyActivityLog
    {
        $log = $this->activityRepository->findByUserAndDate($userId, $date);

        if ($log === null) {
            throw new DomainException("Activity log not found for user {$userId} on {$date}");
        }

        $log = $log->finalize($baseHoldDays);
        $this->activityRepository->save($log);

        // Apply hold reduction to oldest batch
        if ($log->totalHoldReduction > 0) {
            $batches = $this->batchRepository->findActiveByUserId($userId);
            if (!empty($batches)) {
                $oldestBatch = $batches[0];
                $this->applyHoldReduction(
                    $oldestBatch,
                    $log->activityHoldReduction,
                    $log->streakHoldReduction,
                    0 // tier reduction already applied
                );
            }
        }

        $event = $log->dispatchEvent();
        $this->eventDispatcher->dispatch($event);

        return $log;
    }

    /**
     * Applies hold reduction to a batch.
     */
    private function applyHoldReduction(
        LockedBonusBatch $batch,
        int $activityReduction,
        int $streakReduction,
        int $tierReduction
    ): void {
        $updatedHoldPeriod = $this->vestingCalculator->applyHoldAcceleration(
            $batch,
            $activityReduction,
            $streakReduction,
            $tierReduction
        );

        $this->batchRepository->applyHoldAcceleration(
            $batch->id,
            $activityReduction,
            $streakReduction,
            $tierReduction,
            $updatedHoldPeriod->getActualHoldDays()
        );
    }

    /**
     * Processes daily vesting for all active batches.
     */
    public function processDailyVesting(string $date): array
    {
        $batches = $this->batchRepository->findHoldEnded();
        $results = [];

        foreach ($batches as $batch) {
            if ($batch->status === BatchStatus::UNLOCKED) {
                continue;
            }

            $unlockAmount = $batch->processDailyVesting(new DateTimeImmutable($date));

            if ($unlockAmount > 0) {
                $batch = $batch->applyVesting($unlockAmount, new DateTimeImmutable($date));
                $this->batchRepository->updateVesting(
                    $batch->id,
                    $batch->unlockedAmount,
                    $batch->lockedAmount,
                    $batch->status->value
                );

                if ($batch->status === BatchStatus::UNLOCKED) {
                    $event = $batch->dispatchUnlockedEvent(0, 0);
                    $this->eventDispatcher->dispatch($event);
                }

                $results[] = [
                    'batch_id' => $batch->id,
                    'user_id' => $batch->userId,
                    'unlock_amount' => $unlockAmount,
                    'status' => $batch->status->value,
                ];
            }
        }

        return $results;
    }

    /**
     * Gets user's CatFloat summary.
     */
    public function getUserSummary(string $userId): array
    {
        $vestingStats = $this->batchRepository->getVestingStats($userId);
        $engagementStats = $this->activityRepository->getEngagementStats($userId, 30);
        $currentStreak = $this->activityRepository->getCurrentStreak($userId);

        return [
            'vesting' => $vestingStats,
            'engagement' => $engagementStats,
            'current_streak' => $currentStreak,
            'total_locked_balance' => $this->batchRepository->getTotalLockedBalance($userId),
            'total_unlocked_balance' => $this->batchRepository->getTotalUnlockedBalance($userId),
        ];
    }

    /**
     * Gets active batches for a user.
     */
    public function getActiveBatches(string $userId): array
    {
        return $this->batchRepository->findActiveByUserId($userId);
    }

    /**
     * Gets recent activity logs for a user.
     */
    public function getRecentActivity(string $userId, int $limit = 30): array
    {
        return $this->activityRepository->findByUserId($userId, $limit);
    }
}
