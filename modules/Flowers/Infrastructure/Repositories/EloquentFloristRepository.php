<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Repositories;

use Modules\Flowers\Domain\Repositories\FloristRepositoryInterface;
use Modules\Flowers\Domain\Entities\Florist;
use Modules\Flowers\Infrastructure\Models\FloristModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentFloristRepository implements FloristRepositoryInterface
{
    public function findById(int $id): ?Florist
    {
        $model = FloristModel::find($id);
        return $model?->toDomain();
    }

    public function save(Florist $florist): Florist
    {
        $model = FloristModel::fromDomain($florist);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        FloristModel::destroy($id);
    }

    public function getByVenue(int $venueId, array $filters = []): LengthAwarePaginator
    {
        $query = FloristModel::where('venue_id', $venueId);

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getAvailable(int $venueId): array
    {
        return FloristModel::where('venue_id', $venueId)
            ->active()
            ->available()
            ->orderBy('average_rating', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getTopRated(int $venueId): array
    {
        return FloristModel::where('venue_id', $venueId)
            ->active()
            ->topRated()
            ->orderBy('average_rating', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getExperienced(int $venueId): array
    {
        return FloristModel::where('venue_id', $venueId)
            ->active()
            ->experienced()
            ->orderBy('orders_completed', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getByUserId(int $userId): ?Florist
    {
        $model = FloristModel::where('user_id', $userId)->first();
        return $model?->toDomain();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['is_available'])) {
            if ($filters['is_available']) {
                $query->available();
            } else {
                $query->where('is_available', false);
            }
        }

        if (isset($filters['is_active'])) {
            if ($filters['is_active']) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        if (isset($filters['specialization'])) {
            $query->where('specialization', 'like', "%{$filters['specialization']}%");
        }
    }
}
