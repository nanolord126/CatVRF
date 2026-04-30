<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Senior;

use Modules\Fitness\Domain\Senior\Entities\SeniorHealthProfile;
use Modules\Fitness\Domain\Senior\Repositories\SeniorHealthProfileRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Senior\SeniorHealthProfileModel;

final readonly class EloquentSeniorHealthProfileRepository implements SeniorHealthProfileRepositoryInterface
{
    public function save(SeniorHealthProfile $profile): SeniorHealthProfile
    {
        $model = $profile->id === 0
            ? SeniorHealthProfileModel::fromDomain($profile)
            : SeniorHealthProfileModel::where('id', $profile->id)->first();

        if ($model === null) {
            $model = SeniorHealthProfileModel::fromDomain($profile);
        } else {
            $model->updateFromDomain($profile);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?SeniorHealthProfile
    {
        $model = SeniorHealthProfileModel::find($id);

        return $model?->toDomain();
    }

    public function findByClientId(int $clientId): ?SeniorHealthProfile
    {
        $model = SeniorHealthProfileModel::where('client_id', $clientId)->first();

        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        $models = SeniorHealthProfileModel::where('tenant_id', $tenantId)->get();

        return $models->map(fn (SeniorHealthProfileModel $model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        SeniorHealthProfileModel::where('id', $id)->delete();
    }

    public function exists(int $id): bool
    {
        return SeniorHealthProfileModel::where('id', $id)->exists();
    }
}
