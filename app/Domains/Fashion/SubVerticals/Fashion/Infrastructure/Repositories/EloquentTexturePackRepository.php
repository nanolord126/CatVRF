<?php

declare(strict_types=1);

namespace Modules\Fashion\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Fashion\Domain\Entities\TexturePack;
use Modules\Fashion\Domain\Repositories\TexturePackRepositoryInterface;
use Modules\Fashion\Domain\ValueObjects\TextureGenerationStatus;
use Modules\Fashion\Domain\ValueObjects\TextureType;
use Modules\Fashion\Infrastructure\Models\TexturePackModel;

final class EloquentTexturePackRepository implements TexturePackRepositoryInterface
{
    public function save(TexturePack $texturePack): void
    {
        $model = TexturePackModel::where('uuid', $texturePack->getUuid())->first();

        if ($model) {
            $model->update([
                'status' => $texturePack->getStatus()->value,
                'albedo_path' => $texturePack->getAlbedoPath(),
                'normal_path' => $texturePack->getNormalPath(),
                'roughness_path' => $texturePack->getRoughnessPath(),
                'metallic_path' => $texturePack->getMetallicPath(),
                'ao_path' => $texturePack->getAoPath(),
                'displacement_path' => $texturePack->getDisplacementPath(),
                'generation_metadata' => $texturePack->getGenerationMetadata(),
                'correlation_id' => $texturePack->getCorrelationId(),
                'error_message' => $texturePack->getErrorMessage(),
                'completed_at' => $texturePack->getCompletedAt()?->format('Y-m-d H:i:s'),
            ]);
        } else {
            TexturePackModel::create([
                'uuid' => $texturePack->getUuid(),
                'model_3d_id' => $texturePack->getModel3dId(),
                'tenant_id' => auth()->user()?->tenant_id ?? 1,
                'name' => $texturePack->getName(),
                'description' => $texturePack->getDescription(),
                'material_type' => $texturePack->getMaterialType()->value,
                'status' => $texturePack->getStatus()->value,
                'albedo_path' => $texturePack->getAlbedoPath(),
                'normal_path' => $texturePack->getNormalPath(),
                'roughness_path' => $texturePack->getRoughnessPath(),
                'metallic_path' => $texturePack->getMetallicPath(),
                'ao_path' => $texturePack->getAoPath(),
                'displacement_path' => $texturePack->getDisplacementPath(),
                'generation_metadata' => $texturePack->getGenerationMetadata(),
                'correlation_id' => $texturePack->getCorrelationId(),
                'error_message' => $texturePack->getErrorMessage(),
                'completed_at' => $texturePack->getCompletedAt()?->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function findByUuid(string $uuid): ?TexturePack
    {
        $model = TexturePackModel::where('uuid', $uuid)->first();

        return $model?->toDomain();
    }

    public function findByModel3dId(int $model3dId): ?TexturePack
    {
        $model = TexturePackModel::where('model_3d_id', $model3dId)
            ->where('status', TextureGenerationStatus::COMPLETED->value)
            ->latest()
            ->first();

        return $model?->toDomain();
    }

    public function findAllByModel3dId(int $model3dId): Collection
    {
        return TexturePackModel::where('model_3d_id', $model3dId)
            ->get()
            ->map(fn(TexturePackModel $model) => $model->toDomain());
    }

    public function findByStatus(TextureGenerationStatus $status): Collection
    {
        return TexturePackModel::byStatus($status)
            ->get()
            ->map(fn(TexturePackModel $model) => $model->toDomain());
    }

    public function findPendingForProcessing(int $limit = 10): Collection
    {
        return TexturePackModel::pending()
            ->unlocked()
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(fn(TexturePackModel $model) => $model->toDomain());
    }

    public function findByMaterialType(TextureType $materialType): Collection
    {
        return TexturePackModel::byMaterialType($materialType)
            ->get()
            ->map(fn(TexturePackModel $model) => $model->toDomain());
    }

    public function findByCorrelationId(string $correlationId): ?TexturePack
    {
        $model = TexturePackModel::where('correlation_id', $correlationId)->first();

        return $model?->toDomain();
    }

    public function findRetryableFailed(int $maxRetries = 3): Collection
    {
        return TexturePackModel::retryable($maxRetries)
            ->orderBy('retry_count', 'asc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn(TexturePackModel $model) => $model->toDomain());
    }

    public function countByStatus(TextureGenerationStatus $status): int
    {
        return TexturePackModel::byStatus($status)->count();
    }

    public function getStatistics(): array
    {
        $stats = TexturePackModel::selectRaw('
            status,
            COUNT(*) as count,
            AVG(generation_time_ms) as avg_generation_time_ms,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_completed,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_failed
        ', [TextureGenerationStatus::COMPLETED->value, TextureGenerationStatus::FAILED->value])
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->toArray();

        $total = TexturePackModel::count();
        $successRate = $total > 0
            ? (($stats[TextureGenerationStatus::COMPLETED->value]['count'] ?? 0) / $total) * 100
            : 0;

        return [
            'total' => $total,
            'by_status' => $stats,
            'success_rate' => round($successRate, 2),
            'average_generation_time_seconds' => $stats[TextureGenerationStatus::COMPLETED->value]['avg_generation_time_ms'] ?? 0
                ? round(($stats[TextureGenerationStatus::COMPLETED->value]['avg_generation_time_ms'] / 1000), 2)
                : 0,
        ];
    }

    public function delete(TexturePack $texturePack): void
    {
        TexturePackModel::where('uuid', $texturePack->getUuid())->delete();
    }

    public function deleteByUuid(string $uuid): bool
    {
        return TexturePackModel::where('uuid', $uuid)->delete() > 0;
    }

    public function existsForModel3dId(int $model3dId): bool
    {
        return TexturePackModel::where('model_3d_id', $model3dId)
            ->where('status', TextureGenerationStatus::COMPLETED->value)
            ->exists();
    }

    public function findLatestCompletedByModel3dId(int $model3dId): ?TexturePack
    {
        $model = TexturePackModel::where('model_3d_id', $model3dId)
            ->completed()
            ->latest('completed_at')
            ->first();

        return $model?->toDomain();
    }

    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): Collection
    {
        return TexturePackModel::whereBetween('created_at', [
            $startDate->format('Y-m-d H:i:s'),
            $endDate->format('Y-m-d H:i:s'),
        ])
            ->get()
            ->map(fn(TexturePackModel $model) => $model->toDomain());
    }

    public function findWithError(int $limit = 50): Collection
    {
        return TexturePackModel::failed()
            ->whereNotNull('error_message')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn(TexturePackModel $model) => $model->toDomain());
    }

    public function updateStatus(string $uuid, TextureGenerationStatus $status): void
    {
        TexturePackModel::where('uuid', $uuid)->update([
            'status' => $status->value,
        ]);
    }

    public function updateError(string $uuid, string $errorMessage): void
    {
        TexturePackModel::where('uuid', $uuid)->update([
            'error_message' => $errorMessage,
            'status' => TextureGenerationStatus::FAILED->value,
            'completed_at' => now(),
        ]);
    }

    public function getAverageGenerationTime(): float
    {
        $avg = TexturePackModel::completed()
            ->whereNotNull('generation_time_ms')
            ->avg('generation_time_ms');

        return $avg ? $avg / 1000 : 0;
    }

    public function getSuccessRate(): float
    {
        $total = TexturePackModel::count();
        if ($total === 0) {
            return 0.0;
        }

        $completed = TexturePackModel::completed()->count();

        return ($completed / $total) * 100;
    }

    public function findByTenantId(int $tenantId): Collection
    {
        return TexturePackModel::where('tenant_id', $tenantId)
            ->get()
            ->map(fn(TexturePackModel $model) => $model->toDomain());
    }

    public function findByBusinessGroupId(int $businessGroupId): Collection
    {
        return TexturePackModel::where('business_group_id', $businessGroupId)
            ->get()
            ->map(fn(TexturePackModel $model) => $model->toDomain());
    }

    public function findStuckInProcessing(int $thresholdMinutes = 30): Collection
    {
        return TexturePackModel::stuck($thresholdMinutes)
            ->get()
            ->map(fn(TexturePackModel $model) => $model->toDomain());
    }

    public function cleanupOldFailed(int $daysOld = 30): int
    {
        return TexturePackModel::failed()
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    public function getStorageUsage(): array
    {
        $totalSize = TexturePackModel::completed()
            ->get()
            ->sum(function (TexturePackModel $model) {
                $size = 0;
                foreach (['albedo_path', 'normal_path', 'roughness_path', 'metallic_path', 'ao_path', 'displacement_path'] as $field) {
                    if ($model->$field && file_exists(storage_path('app/public/' . $model->$field))) {
                        $size += filesize(storage_path('app/public/' . $model->$field));
                    }
                }

                return $size;
            });

        return [
            'total_bytes' => $totalSize,
            'total_mb' => round($totalSize / 1024 / 1024, 2),
            'total_gb' => round($totalSize / 1024 / 1024 / 1024, 2),
        ];
    }

    public function findByTags(array $tags): Collection
    {
        return TexturePackModel::whereJsonContains('tags', $tags)
            ->get()
            ->map(fn(TexturePackModel $model) => $model->toDomain());
    }

    public function searchByName(string $query): Collection
    {
        return TexturePackModel::where('name', 'like', "%{$query}%")
            ->get()
            ->map(fn(TexturePackModel $model) => $model->toDomain());
    }

    public function getCountByMaterialType(): array
    {
        return TexturePackModel::select('material_type', DB::raw('COUNT(*) as count'))
            ->groupBy('material_type')
            ->pluck('count', 'material_type')
            ->toArray();
    }

    public function getDailyStatistics(int $days = 30): array
    {
        return TexturePackModel::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed', [TextureGenerationStatus::COMPLETED->value]),
            DB::raw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed', [TextureGenerationStatus::FAILED->value]),
        )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->toArray();
    }

    public function lockForProcessing(string $uuid): bool
    {
        return TexturePackModel::where('uuid', $uuid)
            ->where('is_locked', false)
            ->update([
                'is_locked' => true,
                'locked_at' => now(),
                'started_at' => now(),
                'status' => TextureGenerationStatus::PROCESSING->value,
            ]) > 0;
    }

    public function unlockProcessing(string $uuid): void
    {
        TexturePackModel::where('uuid', $uuid)->update([
            'is_locked' => false,
            'locked_at' => null,
        ]);
    }

    public function isLockedForProcessing(string $uuid): bool
    {
        return TexturePackModel::where('uuid', $uuid)->value('is_locked') ?? false;
    }
}
