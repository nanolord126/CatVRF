<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Infrastructure\Persistence;

use App\Domains\Advertising\Domain\Entities\Auction;
use App\Domains\Advertising\Domain\Interfaces\AuctionRepositoryInterface;
use App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models\EloquentAuction;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent Auction Repository
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class EloquentAuctionRepository implements AuctionRepositoryInterface
{
    public function save(Auction $auction): Auction
    {
        $eloquent = $auction->id === 0
            ? EloquentAuction::fromDomain($auction)
            : EloquentAuction::findOrFail($auction->id);

        $eloquent->fill(EloquentAuction::fromDomain($auction)->toArray());
        $eloquent->save();

        return $eloquent->toDomain();
    }

    public function findById(int $id): ?Auction
    {
        $eloquent = EloquentAuction::find($id);

        return $eloquent?->toDomain();
    }

    public function findByUuid(string $uuid): ?Auction
    {
        $eloquent = EloquentAuction::where('uuid', $uuid)->first();

        return $eloquent?->toDomain();
    }

    /** @return Collection<int, Auction> */
    public function findByTenant(int $tenantId): Collection
    {
        return EloquentAuction::where('tenant_id', $tenantId)
            ->get()
            ->map(fn (EloquentAuction $model) => $model->toDomain());
    }

    /** @return Collection<int, Auction> */
    public function findActive(): Collection
    {
        return EloquentAuction::where('status', 'active')
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->get()
            ->map(fn (EloquentAuction $model) => $model->toDomain());
    }

    /** @return Collection<int, Auction> */
    public function findByInventory(int $inventoryId): Collection
    {
        return EloquentAuction::where('inventory_id', $inventoryId)
            ->get()
            ->map(fn (EloquentAuction $model) => $model->toDomain());
    }

    public function updateBidHistory(int $id, array $bidHistory): bool
    {
        return EloquentAuction::where('id', $id)->update(['bid_history' => $bidHistory]) > 0;
    }

    public function updateCurrentPrice(int $id, int $price): bool
    {
        return EloquentAuction::where('id', $id)->update(['current_price' => $price]) > 0;
    }

    public function updateStatus(int $id, string $status): bool
    {
        return EloquentAuction::where('id', $id)->update(['status' => $status]) > 0;
    }

    public function delete(int $id): bool
    {
        return EloquentAuction::where('id', $id)->delete() > 0;
    }
}
