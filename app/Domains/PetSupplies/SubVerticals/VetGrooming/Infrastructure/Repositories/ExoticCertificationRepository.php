<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Repositories;

use Modules\VetGrooming\Domain\Entities\ExoticCertification;
use Modules\VetGrooming\Domain\Repositories\ExoticCertificationRepositoryInterface;
use Modules\VetGrooming\Domain\Enums\CertificationLevel;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;
use Modules\VetGrooming\Infrastructure\Models\ExoticCertificationModel;
use Carbon\CarbonImmutable;

final class ExoticCertificationRepository implements ExoticCertificationRepositoryInterface
{
    public function findById(int $id): ?ExoticCertification
    {
        $model = ExoticCertificationModel::find($id);
        return $model?->toDomain();
    }

    public function findByMasterId(int $masterId): array
    {
        $models = ExoticCertificationModel::where('master_id', $masterId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByMasterAndCategory(int $masterId, ExoticCategory $category): ?ExoticCertification
    {
        $model = ExoticCertificationModel::where('master_id', $masterId)
            ->where('exotic_category', $category->value)
            ->first();
        return $model?->toDomain();
    }

    public function findByMasterAndGroup(int $masterId, ExoticGroup $group): ?ExoticCertification
    {
        $model = ExoticCertificationModel::where('master_id', $masterId)
            ->where('exotic_group', $group->value)
            ->first();
        return $model?->toDomain();
    }

    public function findValidByMasterAndCategory(int $masterId, ExoticCategory $category): ?ExoticCertification
    {
        $model = ExoticCertificationModel::where('master_id', $masterId)
            ->where('exotic_category', $category->value)
            ->where('status', 'active')
            ->where('expiry_date', '>', CarbonImmutable::now())
            ->first();
        return $model?->toDomain();
    }

    public function findValidByMasterAndGroup(int $masterId, ExoticGroup $group): ?ExoticCertification
    {
        $model = ExoticCertificationModel::where('master_id', $masterId)
            ->where('exotic_group', $group->value)
            ->where('status', 'active')
            ->where('expiry_date', '>', CarbonImmutable::now())
            ->first();
        return $model?->toDomain();
    }

    public function findByTenant(int $tenantId, int $limit = 100): array
    {
        $models = ExoticCertificationModel::where('tenant_id', $tenantId)
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByLevel(CertificationLevel $level, int $tenantId, int $limit = 100): array
    {
        $models = ExoticCertificationModel::where('tenant_id', $tenantId)
            ->where('certification_level', $level->value)
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByCategory(ExoticCategory $category, int $tenantId, int $limit = 100): array
    {
        $models = ExoticCertificationModel::where('tenant_id', $tenantId)
            ->where('exotic_category', $category->value)
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findExpiringSoon(int $tenantId, int $days = 30, int $limit = 50): array
    {
        $models = ExoticCertificationModel::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('expiry_date', '<=', CarbonImmutable::now()->addDays($days))
            ->where('expiry_date', '>', CarbonImmutable::now())
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findExpired(int $tenantId, int $limit = 50): array
    {
        $models = ExoticCertificationModel::where('tenant_id', $tenantId)
            ->where('expiry_date', '<', CarbonImmutable::now())
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findRequiringRenewal(int $tenantId, int $limit = 50): array
    {
        $models = ExoticCertificationModel::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('expiry_date', '<=', CarbonImmutable::now()->addDays(30))
            ->limit($limit)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function save(ExoticCertification $certification): ExoticCertification
    {
        $model = ExoticCertificationModel::fromDomain($certification);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        ExoticCertificationModel::destroy($id);
    }

    public function exists(int $masterId, ExoticCategory $category, ExoticGroup $group): bool
    {
        return ExoticCertificationModel::where('master_id', $masterId)
            ->where('exotic_category', $category->value)
            ->where('exotic_group', $group->value)
            ->exists();
    }
}
