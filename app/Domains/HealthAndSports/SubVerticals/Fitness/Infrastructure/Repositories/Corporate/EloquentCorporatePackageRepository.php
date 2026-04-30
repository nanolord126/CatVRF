<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Corporate;

use Modules\Fitness\Domain\Corporate\Entities\CorporatePackage;
use Modules\Fitness\Domain\Corporate\Repositories\CorporatePackageRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Corporate\CorporatePackageModel;

final readonly class EloquentCorporatePackageRepository implements CorporatePackageRepositoryInterface
{
    public function save(CorporatePackage $package): CorporatePackage
    {
        $model = $package->id === 0
            ? CorporatePackageModel::fromDomain($package)
            : CorporatePackageModel::where('id', $package->id)->first();

        if ($model === null) {
            $model = CorporatePackageModel::fromDomain($package);
        } else {
            $model->updateFromDomain($package);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?CorporatePackage
    {
        $model = CorporatePackageModel::find($id);

        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        $models = CorporatePackageModel::where('tenant_id', $tenantId)->get();

        return $models->map(fn (CorporatePackageModel $model) => $model->toDomain())->toArray();
    }

    public function findActiveByTenantId(int $tenantId): array
    {
        $models = CorporatePackageModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        return $models->map(fn (CorporatePackageModel $model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        CorporatePackageModel::where('id', $id)->delete();
    }

    public function exists(int $id): bool
    {
        return CorporatePackageModel::where('id', $id)->exists();
    }
}
