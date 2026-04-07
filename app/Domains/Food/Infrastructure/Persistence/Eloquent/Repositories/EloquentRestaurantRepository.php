<?php

declare(strict_types=1);

namespace App\Domains\Food\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domains\Food\Domain\Entities\Restaurant;
use App\Domains\Food\Domain\Repositories\RestaurantRepositoryInterface;
use App\Domains\Food\Infrastructure\Persistence\Eloquent\Models\RestaurantModel;
use App\Shared\Domain\ValueObjects\TenantId;
use App\Shared\Domain\ValueObjects\Uuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentRestaurantRepository implements RestaurantRepositoryInterface
{
    public function __construct(private readonly RestaurantModel $model)
    {

    }

    public function findById(Uuid $id): ?Restaurant
    {
        $model = $this->model->newQuery()->find($id->toString());

        if (!$model) {
            throw new \DomainException('Unexpected null return in ' . __METHOD__);
        }

        return $this->toEntity($model);
    }

    public function findByTenant(TenantId $tenantId): Collection
    {
        $models = $this->model->newQuery()
            ->where('tenant_id', $tenantId->toString())
            ->get();

        return $models->map(fn (RestaurantModel $model) => $this->toEntity($model));
    }

    public function save(Restaurant $restaurant): void
    {
        $model = $this->model->newQuery()->find($restaurant->id->toString()) ?? new RestaurantModel();

        $model->id = $restaurant->id->toString();
        $model->tenant_id = $restaurant->tenantId->toString();
        $model->name = $restaurant->name;
        $model->description = $restaurant->description;
        $model->address = $restaurant->address->toArray();
        $model->contact = $restaurant->contact->toArray();
        $model->status = $restaurant->status->value;
        $model->schedule = $restaurant->schedule->toArray();
        $model->rating = $restaurant->rating;
        $model->review_count = $restaurant->reviewCount;
        $model->correlation_id = $restaurant->correlationId?->toString();

        $model->save();

        // Here you would sync menu sections, dishes, and modifiers
    }

    public function delete(Uuid $id): bool
    {
        return (bool) $this->model->newQuery()->where('id', $id->toString())->delete();
    }

    /**
     * @param array<string, mixed> $criteria
     * @return Collection<Restaurant>
     */
    public function search(array $criteria): Collection
    {
        $query = $this->model->newQuery();

        $this->applyCriteria($query, $criteria);

        return $query->get()->map(fn (RestaurantModel $model) => $this->toEntity($model));
    }

    private function toEntity(RestaurantModel $model): Restaurant
    {
        // This is a simplified mapping. In a real app, you'd hydrate the full entity
        // including menu sections, dishes, etc., likely through additional queries or relations.
        return Restaurant::fromArray($model->toArray());
    }

    /**
     * @param Builder $query
     * @param array<string, mixed> $criteria
     */
    private function applyCriteria(Builder $query, array $criteria): void
    {
        if (isset($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (isset($criteria['name'])) {
            $query->where('name', 'like', "%{$criteria['name']}%");
        }
    }
}
