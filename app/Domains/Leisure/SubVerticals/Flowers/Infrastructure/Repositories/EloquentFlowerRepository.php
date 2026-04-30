<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Repositories;

use Modules\Flowers\Domain\Repositories\FlowerRepositoryInterface;
use Modules\Flowers\Domain\Entities\Flower;
use Modules\Flowers\Domain\Enums\FreshnessStatus;
use Modules\Flowers\Infrastructure\Models\FlowerModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentFlowerRepository implements FlowerRepositoryInterface
{
    public function findById(int $id): ?Flower
    {
        $model = FlowerModel::find($id);
        return $model?->toDomain();
    }

    public function findBySlug(string $slug): ?Flower
    {
        $model = FlowerModel::where('slug', $slug)->first();
        return $model?->toDomain();
    }

    public function save(Flower $flower): Flower
    {
        $model = FlowerModel::fromDomain($flower);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        FlowerModel::destroy($id);
    }

    public function getByVenue(int $venueId, array $filters = []): LengthAwarePaginator
    {
        $query = FlowerModel::where('venue_id', $venueId);

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getByCategory(int $venueId, string $category): array
    {
        return FlowerModel::where('venue_id', $venueId)
            ->active()
            ->byCategory($category)
            ->orderBy('name')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getByColor(int $venueId, string $color): array
    {
        return FlowerModel::where('venue_id', $venueId)
            ->active()
            ->byColor($color)
            ->orderBy('name')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getLowStock(int $venueId): array
    {
        return FlowerModel::where('venue_id', $venueId)
            ->active()
            ->lowStock()
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getExpiringSoon(int $venueId, int $days = 3): array
    {
        return FlowerModel::where('venue_id', $venueId)
            ->active()
            ->expiringSoon($days)
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getExpired(int $venueId): array
    {
        return FlowerModel::where('venue_id', $venueId)
            ->expired()
            ->orderBy('expiry_date', 'desc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getByFreshnessStatus(int $venueId, FreshnessStatus $status): array
    {
        return FlowerModel::where('venue_id', $venueId)
            ->where('freshness_status', $status)
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getAvailableForReservation(int $venueId, int $flowerId, int $quantity): bool
    {
        $flower = FlowerModel::where('venue_id', $venueId)
            ->where('id', $flowerId)
            ->active()
            ->first();

        if (!$flower) {
            return false;
        }

        return ($flower->stock_quantity - $flower->reserved_quantity) >= $quantity;
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (isset($filters['color'])) {
            $query->byColor($filters['color']);
        }

        if (isset($filters['freshness_status'])) {
            $query->where('freshness_status', $filters['freshness_status']);
        }

        if (isset($filters['is_active'])) {
            if ($filters['is_active']) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }
    }
}
