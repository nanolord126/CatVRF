<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Infrastructure\Repositories;

use App\Domains\Bonuses\DTOs\AwardLockedBonusDto;
use App\Domains\Bonuses\Interfaces\LockedBonusRepositoryInterface;
use App\Domains\Bonuses\Models\LockedBonusBatch;
use Illuminate\Support\Collection;

/**
 * LockedBonusRepository - Infrastructure implementation of locked bonus repository
 * 
 * Implements the domain interface using Eloquent ORM.
 */
final readonly class LockedBonusRepository implements LockedBonusRepositoryInterface
{
    public function create(AwardLockedBonusDto $dto): LockedBonusBatch
    {
        return LockedBonusBatch::create([
            'tenant_id' => $dto->tenantId,
            'user_id' => $dto->userId,
            'original_amount' => $dto->amount,
            'remaining_locked' => $dto->amount,
            'daily_unlock_rate' => $dto->vestingCurve->getDailyRate(),
            'vested_until' => now()->addDays($dto->vestingCurve->getDays()),
            'acceleration_history' => [],
            'source' => $dto->source->getValue(),
            'correlation_id' => $dto->correlationId,
        ]);
    }

    public function findById(int $id): ?LockedBonusBatch
    {
        return LockedBonusBatch::find($id);
    }

    public function findByCorrelationId(string $correlationId): ?LockedBonusBatch
    {
        return LockedBonusBatch::where('correlation_id', $correlationId)->first();
    }

    public function getActiveBatchesForUser(int $userId, int $tenantId): Collection
    {
        return LockedBonusBatch::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->active()
            ->get();
    }

    public function getAllBatchesForUser(int $userId, int $tenantId): Collection
    {
        return LockedBonusBatch::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function getBatchesBySource(int $userId, int $tenantId, string $source): Collection
    {
        return LockedBonusBatch::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('source', $source)
            ->get();
    }

    public function getBatchesVestingSoon(int $userId, int $tenantId, int $days = 3): Collection
    {
        return LockedBonusBatch::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->vestingSoon($days)
            ->get();
    }

    public function getTotalLockedForUser(int $userId, int $tenantId): float
    {
        return LockedBonusBatch::getTotalLockedForUser($userId, $tenantId);
    }

    public function getTotalUnlockedForUser(int $userId, int $tenantId): float
    {
        return LockedBonusBatch::getTotalUnlockedForUser($userId, $tenantId);
    }

    public function getPlatformFloat(?int $tenantId = null): float
    {
        return LockedBonusBatch::getPlatformFloat($tenantId);
    }

    public function update(LockedBonusBatch $batch): bool
    {
        return $batch->save();
    }

    public function delete(LockedBonusBatch $batch): bool
    {
        return $batch->delete();
    }

    public function getBatchesForDailyUnlock(string $date): Collection
    {
        return LockedBonusBatch::where('remaining_locked', '>', 0)
            ->where('vested_until', '>=', $date)
            ->get();
    }

    public function getTenantStatistics(int $tenantId): array
    {
        $totalFloat = $this->getPlatformFloat($tenantId);
        $totalBatches = LockedBonusBatch::where('tenant_id', $tenantId)->count();
        $activeBatches = LockedBonusBatch::where('tenant_id', $tenantId)->active()->count();
        $fullyVested = LockedBonusBatch::where('tenant_id', $tenantId)->fullyVested()->count();

        return [
            'total_float' => $totalFloat,
            'total_batches' => $totalBatches,
            'active_batches' => $activeBatches,
            'fully_vested' => $fullyVested,
        ];
    }
}
