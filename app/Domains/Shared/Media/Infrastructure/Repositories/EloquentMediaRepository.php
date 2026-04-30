<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure\Repositories;

use Modules\Media\Domain\Entities\MediaFile;
use Modules\Media\Domain\Repositories\MediaRepositoryInterface;
use Modules\Media\Domain\ValueObjects\MediaCollectionType;
use Modules\Media\Domain\ValueObjects\MediaStatus;
use Modules\Media\Infrastructure\Models\MediaFileModel;

final readonly class EloquentMediaRepository implements MediaRepositoryInterface
{
    /**
     * @return array<MediaFile>
     */
    public function findByModel(string $modelType, string $modelId): array
    {
        $models = MediaFileModel::query()
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->where('status', '!=', MediaStatus::DELETED)
            ->get();

        return $models->map(fn (MediaFileModel $model) => $model->toDomain())->toArray();
    }

    /**
     * @return array<MediaFile>
     */
    public function findByCollection(string $modelType, string $modelId, MediaCollectionType $collection): array
    {
        $models = MediaFileModel::query()
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->where('collection', $collection)
            ->where('status', '!=', MediaStatus::DELETED)
            ->get();

        return $models->map(fn (MediaFileModel $model) => $model->toDomain())->toArray();
    }

    public function findById(string $id): ?MediaFile
    {
        $model = MediaFileModel::find($id);

        if ($model === null) {
            return null;
        }

        return $model->toDomain();
    }

    public function save(MediaFile $mediaFile): void
    {
        $model = MediaFileModel::fromDomain($mediaFile);
        $model->save();
    }

    public function delete(string $id): void
    {
        $model = MediaFileModel::find($id);

        if ($model !== null) {
            $model->status = MediaStatus::DELETED;
            $model->save();
        }
    }

    public function markAsOptimized(string $id, string $cdnUrl, ?array $additionalMetadata = null): void
    {
        $model = MediaFileModel::find($id);

        if ($model !== null) {
            $model->status = MediaStatus::OPTIMIZED;
            $model->cdn_url = $cdnUrl;

            if ($additionalMetadata !== null) {
                $model->metadata = array_merge($model->metadata ?? [], $additionalMetadata);
            }

            $model->save();
        }
    }

    /**
     * @return array<MediaFile>
     */
    public function findPendingOptimization(int $limit = 50): array
    {
        $models = MediaFileModel::query()
            ->where('status', MediaStatus::PROCESSING)
            ->limit($limit)
            ->get();

        return $models->map(fn (MediaFileModel $model) => $model->toDomain())->toArray();
    }
}
