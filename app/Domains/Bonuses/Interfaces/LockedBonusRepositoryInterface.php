<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Interfaces;

use App\Domains\Bonuses\DTOs\AwardLockedBonusDto;
use App\Domains\Bonuses\Models\LockedBonusBatch;
use Illuminate\Support\Collection;

/**
 * LockedBonusRepositoryInterface - Repository interface for locked bonus batches
 * 
 * Defines contract for locked bonus operations following DDD principles.
 * Implementation in Infrastructure layer.
 */
interface LockedBonusRepositoryInterface
{
    /**
     * Create a new locked bonus batch
     */
    public function create(AwardLockedBonusDto $dto): LockedBonusBatch;

    /**
     * Find batch by ID
     */
    public function findById(int $id): ?LockedBonusBatch;

    /**
     * Find batch by correlation ID
     */
    public function findByCorrelationId(string $correlationId): ?LockedBonusBatch;

    /**
     * Get all active batches for a user
     */
    public function getActiveBatchesForUser(int $userId, int $tenantId): Collection;

    /**
     * Get all batches for a user
     */
    public function getAllBatchesForUser(int $userId, int $tenantId): Collection;

    /**
     * Get batches by source
     */
    public function getBatchesBySource(int $userId, int $tenantId, string $source): Collection;

    /**
     * Get batches vesting soon
     */
    public function getBatchesVestingSoon(int $userId, int $tenantId, int $days = 3): Collection;

    /**
     * Get total locked amount for a user
     */
    public function getTotalLockedForUser(int $userId, int $tenantId): float;

    /**
     * Get total unlocked amount for a user
     */
    public function getTotalUnlockedForUser(int $userId, int $tenantId): float;

    /**
     * Get platform-wide float (total locked)
     */
    public function getPlatformFloat(?int $tenantId = null): float;

    /**
     * Update batch
     */
    public function update(LockedBonusBatch $batch): bool;

    /**
     * Delete batch (soft delete)
     */
    public function delete(LockedBonusBatch $batch): bool;

    /**
     * Get all batches needing daily unlock
     */
    public function getBatchesForDailyUnlock(string $date): Collection;

    /**
     * Get batch statistics for tenant
     */
    public function getTenantStatistics(int $tenantId): array;
}
