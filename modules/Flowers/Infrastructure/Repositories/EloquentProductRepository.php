<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Repositories;

use Modules\Flowers\Domain\Repositories\ProductRepositoryInterface;
use Modules\Flowers\Domain\Entities\Product;
use Modules\Flowers\Infrastructure\Models\ProductModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentProductRepository implements ProductRepositoryInterface
{
    public function findById(int $id): ?Product
    {
        $model = ProductModel::find($id);
        return $model?->toDomain();
    }

    public function findBySlug(string $slug): ?Product
    {
        $model = ProductModel::where('slug', $slug)->first();
        return $model?->toDomain();
    }

    public function save(Product $product): Product
    {
        $model = ProductModel::fromDomain($product);
        $model->save();
        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        ProductModel::destroy($id);
    }

    public function getByVenue(int $venueId, array $filters = []): LengthAwarePaginator
    {
        $query = ProductModel::where('venue_id', $venueId);

        $this->applyFilters($query, $filters);

        return $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getFeatured(int $venueId): array
    {
        return ProductModel::where('venue_id', $venueId)
            ->active()
            ->featured()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getByCategory(int $venueId, string $category): array
    {
        return ProductModel::where('venue_id', $venueId)
            ->active()
            ->byCategory($category)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getBySize(int $venueId, string $size): array
    {
        return ProductModel::where('venue_id', $venueId)
            ->active()
            ->bySize($size)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getOnDiscount(int $venueId): array
    {
        return ProductModel::where('venue_id', $venueId)
            ->active()
            ->onDiscount()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    public function getActive(int $venueId): array
    {
        return ProductModel::where('venue_id', $venueId)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($model) => $model->toDomain())
            ->toArray();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (isset($filters['size'])) {
            $query->bySize($filters['size']);
        }

        if (isset($filters['is_featured'])) {
            if ($filters['is_featured']) {
                $query->featured();
            } else {
                $query->where('is_featured', false);
            }
        }

        if (isset($filters['is_active'])) {
            if ($filters['is_active']) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        if (isset($filters['on_discount'])) {
            if ($filters['on_discount']) {
                $query->onDiscount();
            }
        }
    }
}
