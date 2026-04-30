<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Repositories;

use Modules\VetGrooming\Domain\Entities\ExoticTrainingCompletion;
use Modules\VetGrooming\Domain\Repositories\ExoticTrainingCompletionRepositoryInterface;
use Modules\VetGrooming\Infrastructure\Models\ExoticTrainingCompletionModel;
use Carbon\CarbonImmutable;

final class ExoticTrainingCompletionRepository implements ExoticTrainingCompletionRepositoryInterface
{
    public function findById(int $id): ?ExoticTrainingCompletion
    {
        $model = ExoticTrainingCompletionModel::find($id);
        return $model?->toDomain();
    }

    public function findByMasterId(int $masterId): array
    {
        $models = ExoticTrainingCompletionModel::where('master_id', $masterId)
            ->latest('completed_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByMasterAndCourse(int $masterId, int $courseId): ?ExoticTrainingCompletion
    {
        $model = ExoticTrainingCompletionModel::where('master_id', $masterId)
            ->where('course_id', $courseId)
            ->first();
        return $model?->toDomain();
    }

    public function findByCourseId(int $courseId, int $limit = 100): array
    {
        $models = ExoticTrainingCompletionModel::where('course_id', $courseId)
            ->limit($limit)
            ->latest('completed_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByTenant(int $tenantId, int $limit = 100): array
    {
        $models = ExoticTrainingCompletionModel::where('tenant_id', $tenantId)
            ->limit($limit)
            ->latest('completed_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findPassedByMasterId(int $masterId): array
    {
        $models = ExoticTrainingCompletionModel::where('master_id', $masterId)
            ->where('passed', true)
            ->latest('completed_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findExpiringSoon(int $tenantId, int $days = 30, int $limit = 50): array
    {
        $models = ExoticTrainingCompletionModel::where('tenant_id', $tenantId)
            ->where('expires_at', '<=', CarbonImmutable::now()->addDays($days))
            ->where('expires_at', '>', CarbonImmutable::now())
            ->limit($limit)
            ->latest('expires_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findExpired(int $tenantId, int $limit = 50): array
    {
        $models = ExoticTrainingCompletionModel::where('tenant_id', $tenantId)
            ->where('expires_at', '<', CarbonImmutable::now())
            ->limit($limit)
            ->latest('expires_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function save(ExoticTrainingCompletion $completion): ExoticTrainingCompletion
    {
        $model = ExoticTrainingCompletionModel::fromDomain($completion);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        ExoticTrainingCompletionModel::destroy($id);
    }

    public function hasPassedCourse(int $masterId, int $courseId): bool
    {
        return ExoticTrainingCompletionModel::where('master_id', $masterId)
            ->where('course_id', $courseId)
            ->where('passed', true)
            ->exists();
    }

    public function getMasterCompletionsForLevel(int $masterId, string $targetLevel): array
    {
        $models = ExoticTrainingCompletionModel::where('master_id', $masterId)
            ->whereHas('course', function ($query) use ($targetLevel) {
                $query->where('certification_level', $targetLevel);
            })
            ->where('passed', true)
            ->latest('completed_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }
}
