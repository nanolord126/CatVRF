<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Prenatal;

use Modules\Fitness\Domain\Prenatal\Entities\PrenatalHealthProfile;
use Modules\Fitness\Domain\Prenatal\Repositories\PrenatalHealthProfileRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Prenatal\PrenatalHealthProfileModel;

final readonly class EloquentPrenatalHealthProfileRepository implements PrenatalHealthProfileRepositoryInterface
{
    public function save(PrenatalHealthProfile $profile): PrenatalHealthProfile
    {
        $model = $profile->id > 0
            ? PrenatalHealthProfileModel::findOrFail($profile->id)
            : new PrenatalHealthProfileModel();

        if ($profile->id > 0) {
            $model->updateFromDomain($profile);
        } else {
            $model = PrenatalHealthProfileModel::fromDomain($profile);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?PrenatalHealthProfile
    {
        $model = PrenatalHealthProfileModel::find($id);
        return $model?->toDomain();
    }

    public function findByClientId(int $clientId): ?PrenatalHealthProfile
    {
        $model = PrenatalHealthProfileModel::where('client_id', $clientId)->first();
        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        $models = PrenatalHealthProfileModel::where('tenant_id', $tenantId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        PrenatalHealthProfileModel::findOrFail($id)->delete();
    }

    public function exists(int $id): bool
    {
        return PrenatalHealthProfileModel::where('id', $id)->exists();
    }
}
