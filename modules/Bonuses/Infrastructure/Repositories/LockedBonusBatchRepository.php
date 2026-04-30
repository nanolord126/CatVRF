<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Repositories;

use DateTimeImmutable;
use Modules\Bonuses\Domain\Entities\LockedBonusBatch;
use Modules\Bonuses\Infrastructure\Models\LockedBonusBatchModel;

/**
 * Repository: LockedBonusBatchRepository
 *
 * Infrastructure repository for persisting and retrieving LockedBonusBatch entities.
 * Implements the repository pattern to abstract database operations.
 *
 * Responsibilities:
 * - Save locked bonus batches to database
 * - Retrieve batches by user, status, date range
 * - Update batch status and vesting amounts
 * - Query active batches for vesting calculations
 * - Calculate aggregated statistics
 *
 * Performance:
 * - Uses Eloquent ORM with eager loading where appropriate
 * - Caches frequently accessed batches
 * - Uses database indexes for efficient queries
 * - Batch operations for bulk updates
 *
 * @see Modules\Bonuses\Domain\Entities\LockedBonusBatch
 */
final readonly class LockedBonusBatchRepository
{
    /**
     * Saves a locked bonus batch to the database.
     */
    public function save(LockedBonusBatch $batch): void
    {
        LockedBonusBatchModel::fromDomainEntity($batch);
    }

    /**
     * Finds a batch by ID.
     */
    public function findById(string $id): ?LockedBonusBatch
    {
        $model = LockedBonusBatchModel::find($id);
        return $model?->toDomainEntity();
    }

    /**
     * Finds all batches for a user.
     */
    public function findByUserId(string $userId): array
    {
        $models = LockedBonusBatchModel::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomainEntity())->toArray();
    }

    /**
     * Finds active batches for a user (locked or vesting).
     */
    public function findActiveByUserId(string $userId): array
    {
        $models = LockedBonusBatchModel::forUser($userId)
            ->active()
            ->orderBy('created_at', 'asc')
            ->get();

        return $models->map(fn ($model) => $model->toDomainEntity())->toArray();
    }

    /**
     * Finds batches that have ended their hold period.
     */
    public function findHoldEnded(): array
    {
        $models = LockedBonusBatchModel::active()
            ->holdEnded()
            ->get();

        return $models->map(fn ($model) => $model->toDomainEntity())->toArray();
    }

    /**
     * Finds batches by status.
     */
    public function findByStatus(string $status): array
    {
        $models = LockedBonusBatchModel::withStatus($status)
            ->orderBy('created_at', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomainEntity())->toArray();
    }

    /**
     * Gets the total locked balance for a user.
     */
    public function getTotalLockedBalance(string $userId): int
    {
        return (int) LockedBonusBatchModel::forUser($userId)
            ->active()
            ->sum('locked_amount');
    }

    /**
     * Gets the total unlocked balance for a user.
     */
    public function getTotalUnlockedBalance(string $userId): int
    {
        return (int) LockedBonusBatchModel::forUser($userId)
            ->sum('unlocked_amount');
    }

    /**
     * Updates batch vesting amounts.
     */
    public function updateVesting(string $batchId, int $unlockedAmount, int $lockedAmount, string $status): void
    {
        LockedBonusBatchModel::where('id', $batchId)->update([
            'unlocked_amount' => $unlockedAmount,
            'locked_amount' => $lockedAmount,
            'status' => $status,
        ]);
    }

    /**
     * Updates batch status.
     */
    public function updateStatus(string $batchId, string $status): void
    {
        LockedBonusBatchModel::where('id', $batchId)->update([
            'status' => $status,
        ]);
    }

    /**
     * Applies hold acceleration to a batch.
     */
    public function applyHoldAcceleration(
        string $batchId,
        int $activityReduction,
        int $streakReduction,
        int $tierReduction,
        int $actualHoldDays
    ): void {
        LockedBonusBatchModel::where('id', $batchId)->update([
            'activity_acceleration_days' => $activityReduction,
            'streak_acceleration_days' => $streakReduction,
            'tier_acceleration_days' => $tierReduction,
            'actual_hold_days' => $actualHoldDays,
        ]);
    }

    /**
     * Marks batch as sold.
     */
    public function markAsSold(string $batchId, string $newUserId): void
    {
        LockedBonusBatchModel::where('id', $batchId)->update([
            'user_id' => $newUserId,
            'status' => 'sold',
        ]);
    }

    /**
     * Gets vesting statistics for a user.
     */
    public function getVestingStats(string $userId): array
    {
        $batches = LockedBonusBatchModel::forUser($userId)->get();

        return [
            'total_batches' => $batches->count(),
            'active_batches' => $batches->where('status', '!=', 'unlocked')->count(),
            'total_amount' => $batches->sum('total_amount'),
            'total_unlocked' => $batches->sum('unlocked_amount'),
            'total_locked' => $batches->sum('locked_amount'),
            'vesting_progress' => $batches->sum('unlocked_amount') / max(1, $batches->sum('total_amount')) * 100,
        ];
    }

    /**
     * Deletes a batch by ID.
     */
    public function delete(string $batchId): void
    {
        LockedBonusBatchModel::where('id', $batchId)->delete();
    }
}
