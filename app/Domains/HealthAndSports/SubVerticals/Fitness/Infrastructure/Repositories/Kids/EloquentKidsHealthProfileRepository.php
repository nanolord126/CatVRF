<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Kids;

use Modules\Fitness\Domain\Kids\Entities\KidsHealthProfile;
use Modules\Fitness\Domain\Kids\Repositories\KidsHealthProfileRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Kids\KidsHealthProfileModel;

final readonly class EloquentKidsHealthProfileRepository implements KidsHealthProfileRepositoryInterface
{
    public function save(KidsHealthProfile $profile): KidsHealthProfile
    {
        $model = $profile->id > 0
            ? KidsHealthProfileModel::findOrFail($profile->id)
            : new KidsHealthProfileModel();

        if ($profile->id > 0) {
            $model->updateFromDomain($profile);
        } else {
            $model = KidsHealthProfileModel::fromDomain($profile);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?KidsHealthProfile
    {
        $model = KidsHealthProfileModel::find($id);
        return $model?->toDomain();
    }

    public function findByClientId(int $clientId): ?KidsHealthProfile
    {
        $model = KidsHealthProfileModel::where('client_id', $clientId)->first();
        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        $models = KidsHealthProfileModel::where('tenant_id', $tenantId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByAgeGroup(int $tenantId, string $ageGroup): array
    {
        $models = KidsHealthProfileModel::where('tenant_id', $tenantId)
            ->where('age_group', $ageGroup)
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        KidsHealthProfileModel::findOrFail($id)->delete();
    }

    public function exists(int $id): bool
    {
        return KidsHealthProfileModel::where('id', $id)->exists();
    }
}
