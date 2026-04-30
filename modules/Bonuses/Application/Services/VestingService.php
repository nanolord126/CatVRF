<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\Services;

use DateTimeImmutable;
use DomainException;
use Illuminate\Events\Dispatcher;
use Modules\Bonuses\Domain\Entities\LockedBonusBatch;
use Modules\Bonuses\Domain\Enums\BatchStatus;
use Modules\Bonuses\Domain\Events\BonusUnlocked;
use Modules\Bonuses\Domain\Interfaces\VestingCalculatorInterface;
use Modules\Bonuses\Infrastructure\Repositories\LockedBonusBatchRepository;

/**
 * Service: VestingService
 *
 * Application service for managing vesting schedules and calculations.
 * Handles daily vesting operations and hold period accelerations.
 *
 * Core responsibilities:
 * - Calculate daily vesting amounts for batches
 * - Apply vesting to batches and update status
 * - Calculate hold period reductions from activity, streaks, and tier
 * - Determine when vesting is complete
 * - Generate vesting schedules for display
 *
 * No facades - all dependencies injected via constructor.
 *
 * @see Modules\Bonuses\Domain\Entities\LockedBonusBatch
 * @see Modules\Bonuses\Domain\Interfaces\VestingCalculatorInterface
 */
final readonly class VestingService
{
    public function __construct(
        private LockedBonusBatchRepository $batchRepository,
        private VestingCalculatorInterface $vestingCalculator,
        private Dispatcher $eventDispatcher
    ) {
    }

    /**
     * Processes vesting for a specific batch.
     */
    public function processBatchVesting(string $batchId, DateTimeImmutable $now): array
    {
        $batch = $this->batchRepository->findById($batchId);

        if ($batch === null) {
            throw new DomainException("Batch not found: {$batchId}");
        }

        if ($batch->isFullyUnlocked()) {
            return [
                'batch_id' => $batchId,
                'unlock_amount' => 0,
                'status' => $batch->status->value,
                'already_unlocked' => true,
            ];
        }

        $unlockAmount = $batch->processDailyVesting($now);

        if ($unlockAmount > 0) {
            $batch = $batch->applyVesting($unlockAmount, $now);
            $this->batchRepository->updateVesting(
                $batchId,
                $batch->unlockedAmount,
                $batch->lockedAmount,
                $batch->status->value
            );

            if ($batch->status === BatchStatus::UNLOCKED) {
                $event = $batch->dispatchUnlockedEvent(0, 0);
                $this->eventDispatcher->dispatch($event);
            }
        }

        return [
            'batch_id' => $batchId,
            'unlock_amount' => $unlockAmount,
            'status' => $batch->status->value,
            'vesting_progress' => $batch->getVestingProgress(),
            'days_remaining' => $batch->getDaysRemaining($now),
        ];
    }

    /**
     * Processes vesting for all active batches.
     */
    public function processAllVesting(DateTimeImmutable $now): array
    {
        $batches = $this->batchRepository->findHoldEnded();
        $results = [];

        foreach ($batches as $batch) {
            $result = $this->processBatchVesting($batch->id, $now);
            $results[] = $result;
        }

        return [
            'processed_count' => count($results),
            'total_unlocked' => array_sum(array_column($results, 'unlock_amount')),
            'batches' => $results,
        ];
    }

    /**
     * Applies hold period acceleration to a batch.
     */
    public function applyHoldAcceleration(
        string $batchId,
        int $activityReduction,
        int $streakReduction,
        int $tierReduction
    ): LockedBonusBatch {
        $batch = $this->batchRepository->findById($batchId);

        if ($batch === null) {
            throw new DomainException("Batch not found: {$batchId}");
        }

        $updatedHoldPeriod = $this->vestingCalculator->applyHoldAcceleration(
            $batch,
            $activityReduction,
            $streakReduction,
            $tierReduction
        );

        $this->batchRepository->applyHoldAcceleration(
            $batchId,
            $activityReduction,
            $streakReduction,
            $tierReduction,
            $updatedHoldPeriod->getActualHoldDays()
        );

        return $batch;
    }

    /**
     * Generates vesting schedule for a batch.
     */
    public function generateVestingSchedule(string $batchId): array
    {
        $batch = $this->batchRepository->findById($batchId);

        if ($batch === null) {
            throw new DomainException("Batch not found: {$batchId}");
        }

        return $this->vestingCalculator->generateVestingSchedule($batch);
    }

    /**
     * Gets vesting progress for a batch.
     */
    public function getVestingProgress(string $batchId): array
    {
        $batch = $this->batchRepository->findById($batchId);

        if ($batch === null) {
            throw new DomainException("Batch not found: {$batchId}");
        }

        $now = new DateTimeImmutable();

        return [
            'batch_id' => $batchId,
            'total_amount' => $batch->totalAmount,
            'unlocked_amount' => $batch->unlockedAmount,
            'locked_amount' => $batch->lockedAmount,
            'vesting_progress' => $batch->getVestingProgress(),
            'is_fully_unlocked' => $batch->isFullyUnlocked(),
            'status' => $batch->status->value,
            'days_remaining' => $batch->getDaysRemaining($now),
            'fully_available_at' => $batch->fullyAvailableAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Gets user's vesting summary.
     */
    public function getUserVestingSummary(string $userId): array
    {
        return $this->batchRepository->getVestingStats($userId);
    }

    /**
     * Calculates estimated unlock date for a batch.
     */
    public function estimateUnlockDate(string $batchId): string
    {
        $batch = $this->batchRepository->findById($batchId);

        if ($batch === null) {
            throw new DomainException("Batch not found: {$batchId}");
        }

        return $this->vestingCalculator->estimateUnlockDate($batch)->format('Y-m-d H:i:s');
    }

    /**
     * Checks if batch vesting is complete.
     */
    public function isVestingComplete(string $batchId): bool
    {
        $batch = $this->batchRepository->findById($batchId);

        if ($batch === null) {
            throw new DomainException("Batch not found: {$batchId}");
        }

        return $this->vestingCalculator->isVestingComplete($batch, new DateTimeImmutable());
    }

    /**
     * Gets days remaining for a batch.
     */
    public function getDaysRemaining(string $batchId): int
    {
        $batch = $this->batchRepository->findById($batchId);

        if ($batch === null) {
            throw new DomainException("Batch not found: {$batchId}");
        }

        return $this->vestingCalculator->getDaysRemaining($batch, new DateTimeImmutable());
    }
}
