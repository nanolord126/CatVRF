<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Repositories;

use Modules\Flowers\Domain\Repositories\VenueRepositoryInterface;
use Modules\Flowers\Domain\Entities\Venue;
use Modules\Flowers\Infrastructure\Models\VenueModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentVenueRepository implements VenueRepositoryInterface
{
    public function findById(int $id): ?Venue
    {
        $model = VenueModel::find($id);
        return $model?->toDomain();
    }

    public function findBySlug(string $slug): ?Venue
    {
        $model = VenueModel::where('slug', $slug)->first();
        return $model?->toDomain();
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

    public function getByTenant(int $tenantId, array $filters = []): LengthAwarePaginator
    {
        $query = VenueModel::where('tenant_id', $tenantId);

        $this->applyFilters($query, $filters);

        return $query->orderBy('name')
            ->paginate(20);
    }

    public function getByCity(string $city): array
    {
        return VenueModel::byCity($city)
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getActive(): array
    {
        return VenueModel::active()
            ->orderBy('name')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function supportsDelivery(): array
    {
        return VenueModel::active()
            ->supportsDelivery()
            ->orderBy('name')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['city'])) {
            $query->byCity($filters['city']);
        }

        if (isset($filters['is_active'])) {
            if ($filters['is_active']) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        if (isset($filters['supports_delivery'])) {
            $query->where('supports_delivery', $filters['supports_delivery']);
        }
    }
}
