<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Repositories;

use Modules\VetGrooming\Domain\Entities\ExoticTrainingCourse;
use Modules\VetGrooming\Domain\Repositories\ExoticTrainingCourseRepositoryInterface;
use Modules\VetGrooming\Domain\Enums\CertificationLevel;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;
use Modules\VetGrooming\Infrastructure\Models\ExoticTrainingCourseModel;

final class ExoticTrainingCourseRepository implements ExoticTrainingCourseRepositoryInterface
{
    public function findById(int $id): ?ExoticTrainingCourse
    {
        $model = ExoticTrainingCourseModel::find($id);
        return $model?->toDomain();
    }

    public function findByTenant(int $tenantId, int $limit = 100): array
    {
        $models = ExoticTrainingCourseModel::where('tenant_id', $tenantId)
            ->limit($limit)
            ->latest('created_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findActiveByTenant(int $tenantId, int $limit = 100): array
    {
        $models = ExoticTrainingCourseModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->limit($limit)
            ->latest('created_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByCategory(ExoticCategory $category, int $tenantId, int $limit = 100): array
    {
        $models = ExoticTrainingCourseModel::where('tenant_id', $tenantId)
            ->where('exotic_category', $category->value)
            ->limit($limit)
            ->latest('created_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByGroup(ExoticGroup $group, int $tenantId, int $limit = 100): array
    {
        $models = ExoticTrainingCourseModel::where('tenant_id', $tenantId)
            ->where('exotic_group', $group->value)
            ->limit($limit)
            ->latest('created_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByLevel(CertificationLevel $level, int $tenantId, int $limit = 100): array
    {
        $models = ExoticTrainingCourseModel::where('tenant_id', $tenantId)
            ->where('certification_level', $level->value)
            ->limit($limit)
            ->latest('created_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findMandatoryByTenant(int $tenantId, int $limit = 100): array
    {
        $models = ExoticTrainingCourseModel::where('tenant_id', $tenantId)
            ->where('is_mandatory', true)
            ->limit($limit)
            ->latest('created_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findMandatoryForCategory(ExoticCategory $category, int $tenantId, int $limit = 100): array
    {
        $models = ExoticTrainingCourseModel::where('tenant_id', $tenantId)
            ->where('exotic_category', $category->value)
            ->where('is_mandatory', true)
            ->limit($limit)
            ->latest('created_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByCategoryAndLevel(ExoticCategory $category, CertificationLevel $level, int $tenantId, int $limit = 100): array
    {
        $models = ExoticTrainingCourseModel::where('tenant_id', $tenantId)
            ->where('exotic_category', $category->value)
            ->where('certification_level', $level->value)
            ->limit($limit)
            ->latest('created_at')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function save(ExoticTrainingCourse $course): ExoticTrainingCourse
    {
        $model = ExoticTrainingCourseModel::fromDomain($course);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        ExoticTrainingCourseModel::destroy($id);
    }
}
