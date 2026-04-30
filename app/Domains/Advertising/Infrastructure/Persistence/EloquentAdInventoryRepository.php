<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Infrastructure\Persistence;

use App\Domains\Advertising\Domain\Entities\AdInventory;
use App\Domains\Advertising\Domain\Interfaces\AdInventoryRepositoryInterface;
use App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models\EloquentAdInventory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent AdInventory Repository
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class EloquentAdInventoryRepository implements AdInventoryRepositoryInterface
{
    public function save(AdInventory $inventory): AdInventory
    {
        $eloquent = $inventory->id === 0
            ? EloquentAdInventory::fromDomain($inventory)
            : EloquentAdInventory::findOrFail($inventory->id);

        $eloquent->fill(EloquentAdInventory::fromDomain($inventory)->toArray());
        $eloquent->save();

        return $eloquent->toDomain();
    }

    public function findById(int $id): ?AdInventory
    {
        $eloquent = EloquentAdInventory::find($id);

        return $eloquent?->toDomain();
    }

    public function findByUuid(string $uuid): ?AdInventory
    {
        $eloquent = EloquentAdInventory::where('uuid', $uuid)->first();

        return $eloquent?->toDomain();
    }

    /** @return Collection<int, AdInventory> */
    public function findByPublisher(int $publisherId): Collection
    {
        return EloquentAdInventory::where('publisher_id', $publisherId)
            ->get()
            ->map(fn (EloquentAdInventory $model) => $model->toDomain());
    }

    /** @return Collection<int, AdInventory> */
    public function findAvailable(
        string $inventoryType,
        string $placement,
        array $targeting = []
    ): Collection {
        $query = EloquentAdInventory::where('inventory_type', $inventoryType)
            ->where('placement', $placement)
            ->where('status', 'available')
            ->where('available_from', '<=', now())
            ->where('available_until', '>', now())
            ->whereRaw('available_impressions > reserved_impressions');

        if (!empty($targeting)) {
            // JSON-based targeting filter (PostgreSQL/MySQL compatible)
            foreach ($targeting as $key => $value) {
                $query->whereJsonContains('targeting_restrictions', [$key => $value]);
            }
        }

        return $query->get()
            ->map(fn (EloquentAdInventory $model) => $model->toDomain());
    }

    /** @return Collection<int, AdInventory> */
    public function findActive(): Collection
    {
        return EloquentAdInventory::where('status', 'available')
            ->where('available_from', '<=', now())
            ->where('available_until', '>', now())
            ->get()
            ->map(fn (EloquentAdInventory $model) => $model->toDomain());
    }

    public function reserve(int $id, int $impressions): bool
    {
        return DB::transaction(function () use ($id, $impressions) {
            $inventory = EloquentAdInventory::lockForUpdate()->findOrFail($id);

            if (($inventory->reserved_impressions + $impressions) > $inventory->available_impressions) {
                return false;
            }

            $inventory->reserved_impressions += $impressions;
            $inventory->status = ($inventory->available_impressions - $inventory->reserved_impressions) === 0
                ? 'sold_out'
                : 'reserved';
            $inventory->save();

            return true;
        });
    }

    public function release(int $id, int $impressions): bool
    {
        return DB::transaction(function () use ($id, $impressions) {
            $inventory = EloquentAdInventory::lockForUpdate()->findOrFail($id);

            $inventory->reserved_impressions = max(0, $inventory->reserved_impressions - $impressions);
            $inventory->status = $inventory->reserved_impressions === 0
                ? 'available'
                : $inventory->status;
            $inventory->save();

            return true;
        });
    }

    public function updateStatus(int $id, string $status): bool
    {
        return EloquentAdInventory::where('id', $id)->update(['status' => $status]) > 0;
    }

    public function delete(int $id): bool
    {
        return EloquentAdInventory::where('id', $id)->delete() > 0;
    }
}
