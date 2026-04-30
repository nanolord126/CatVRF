<?php

declare(strict_types=1);

namespace Modules\Media\Domain\Repositories;

use Modules\Media\Domain\Entities\MediaFile;
use Modules\Media\Domain\ValueObjects\MediaCollectionType;
use Modules\Media\Domain\ValueObjects\MediaStatus;

interface MediaRepositoryInterface
{
    /**
     * @return array<MediaFile>
     */
    public function findByModel(string $modelType, string $modelId): array;

    /**
     * @return array<MediaFile>
     */
    public function findByCollection(string $modelType, string $modelId, MediaCollectionType $collection): array;

    public function findById(string $id): ?MediaFile;

    public function save(MediaFile $mediaFile): void;

    public function delete(string $id): void;

    public function markAsOptimized(string $id, string $cdnUrl, ?array $additionalMetadata = null): void;

    /**
     * @return array<MediaFile>
     */
    public function findPendingOptimization(int $limit = 50): array;
}
