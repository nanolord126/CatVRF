<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Repositories;

use Modules\Hotels\Domain\Entities\Service;
use Modules\Hotels\Domain\Repositories\ServiceRepositoryInterface;
use Modules\Hotels\Domain\ValueObjects\ServiceId;
use Modules\Hotels\Domain\ValueObjects\VenueId;
use Modules\Hotels\Domain\ValueObjects\TenantId;
use Modules\Hotels\Infrastructure\Models\ServiceModel;

final class EloquentServiceRepository implements ServiceRepositoryInterface
{
    public function save(Service $service): void
    {
        $model = $service->id === 0
            ? ServiceModel::fromDomain($service)
            : ServiceModel::findOrFail($service->id);

        $model->fill(ServiceModel::fromDomain($service)->toArray());
        $model->save();

        if ($service->id === 0) {
            $model->id = $model->fresh()->id;
        }
    }

    public function findById(ServiceId $id): ?Service
    {
        $model = ServiceModel::find($id->value);
        return $model?->toDomain();
    }

    public function findByVenue(VenueId $venueId): array
    {
        return ServiceModel::where('venue_id', $venueId->value)
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findByTenant(TenantId $tenantId): array
    {
        return ServiceModel::where('tenant_id', $tenantId->value)
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function findAvailableByVenue(VenueId $venueId): array
    {
        return ServiceModel::where('venue_id', $venueId->value)
            ->where('is_available', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function delete(ServiceId $id): void
    {
        ServiceModel::findOrFail($id->value)->delete();
    }
}
