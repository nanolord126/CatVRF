<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories;

use Modules\Fitness\Domain\Entities\Venue;
use Modules\Fitness\Domain\Repositories\VenueRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\VenueModel;

final class EloquentVenueRepository implements VenueRepositoryInterface
{
    public function findById(int $id): ?Venue
    {
        $model = VenueModel::find($id);
        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        return VenueModel::where('tenant_id', $tenantId)
            ->get()
            ->map(fn (VenueModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findActiveByTenantId(int $tenantId): array
    {
        return VenueModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->map(fn (VenueModel $model) => $model->toDomain())
            ->toArray();
    }

    public function findByCity(int $tenantId, string $city): array
    {
        return VenueModel::where('tenant_id', $tenantId)
            ->where('city', $city)
            ->get()
            ->map(fn (VenueModel $model) => $model->toDomain())
            ->toArray();
    }

    public function save(Venue $venue): Venue
    {
        $model = VenueModel::fromDomain($venue);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        VenueModel::destroy($id);
    }
}
