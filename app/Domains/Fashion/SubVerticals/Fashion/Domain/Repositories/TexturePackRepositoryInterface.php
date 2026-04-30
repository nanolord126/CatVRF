<?php

declare(strict_types=1);

namespace Modules\Fashion\Domain\Repositories;

use Modules\Fashion\Domain\Entities\TexturePack;
use Modules\Fashion\Domain\ValueObjects\TextureGenerationStatus;
use Modules\Fashion\Domain\ValueObjects\TextureType;
use Illuminate\Support\Collection;

interface TexturePackRepositoryInterface
{
    /**
     * Save a texture pack to storage
     */
    public function save(TexturePack $texturePack): void;

    /**
     * Find texture pack by UUID
     */
    public function findByUuid(string $uuid): ?TexturePack;

    /**
     * Find texture pack by model 3D ID
     */
    public function findByModel3dId(int $model3dId): ?TexturePack;

    /**
     * Find all texture packs for a model 3D
     */
    public function findAllByModel3dId(int $model3dId): Collection;

    /**
     * Find texture packs by status
     */
    public function findByStatus(TextureGenerationStatus $status): Collection;

    /**
     * Find pending texture packs for processing
     */
    public function findPendingForProcessing(int $limit = 10): Collection;

    /**
     * Find texture packs by material type
     */
    public function findByMaterialType(TextureType $materialType): Collection;

    /**
     * Find texture packs by correlation ID
     */
    public function findByCorrelationId(string $correlationId): ?TexturePack;

    /**
     * Get texture packs that need retry (failed but within retry limit)
     */
    public function findRetryableFailed(int $maxRetries = 3): Collection;

    /**
     * Count texture packs by status
     */
    public function countByStatus(TextureGenerationStatus $status): int;

    /**
     * Get statistics for texture generation
     */
    public function getStatistics(): array;

    /**
     * Delete texture pack
     */
    public function delete(TexturePack $texturePack): void;

    /**
     * Delete texture pack by UUID
     */
    public function deleteByUuid(string $uuid): bool;

    /**
     * Check if texture pack exists for model 3D
     */
    public function existsForModel3dId(int $model3dId): bool;

    /**
     * Get latest completed texture pack for model 3D
     */
    public function findLatestCompletedByModel3dId(int $model3dId): ?TexturePack;

    /**
     * Get texture packs created within date range
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): Collection;

    /**
     * Get texture packs with errors for monitoring
     */
    public function findWithError(int $limit = 50): Collection;

    /**
     * Update texture pack status
     */
    public function updateStatus(string $uuid, TextureGenerationStatus $status): void;

    /**
     * Update texture pack with error message
     */
    public function updateError(string $uuid, string $errorMessage): void;

    /**
     * Get average generation time for completed texture packs
     */
    public function getAverageGenerationTime(): float;

    /**
     * Get success rate for texture generation
     */
    public function getSuccessRate(): float;

    /**
     * Find texture packs by tenant ID
     */
    public function findByTenantId(int $tenantId): Collection;

    /**
     * Find texture packs by business group ID
     */
    public function findByBusinessGroupId(int $businessGroupId): Collection;

    /**
     * Get texture packs that are taking too long (stuck in processing)
     */
    public function findStuckInProcessing(int $thresholdMinutes = 30): Collection;

    /**
     * Cleanup old failed texture packs
     */
    public function cleanupOldFailed(int $daysOld = 30): int;

    /**
     * Get texture pack storage usage
     */
    public function getStorageUsage(): array;

    /**
     * Find texture packs by tags
     */
    public function findByTags(array $tags): Collection;

    /**
     * Search texture packs by name
     */
    public function searchByName(string $query): Collection;

    /**
     * Get texture pack count per material type
     */
    public function getCountByMaterialType(): array;

    /**
     * Get daily generation statistics
     */
    public function getDailyStatistics(int $days = 30): array;

    /**
     * Lock texture pack for processing (to prevent concurrent processing)
     */
    public function lockForProcessing(string $uuid): bool;

    /**
     * Unlock texture pack after processing
     */
    public function unlockProcessing(string $uuid): void;

    /**
     * Check if texture pack is locked for processing
     */
    public function isLockedForProcessing(string $uuid): bool;
}
