<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Infrastructure\Persistence;

use App\Domains\Advertising\Domain\Entities\Bid;
use App\Domains\Advertising\Domain\Interfaces\BidRepositoryInterface;
use App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models\EloquentBid;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent Bid Repository
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class EloquentBidRepository implements BidRepositoryInterface
{
    public function save(Bid $bid): Bid
    {
        $eloquent = $bid->id === 0
            ? EloquentBid::fromDomain($bid)
            : EloquentBid::findOrFail($bid->id);

        $eloquent->fill(EloquentBid::fromDomain($bid)->toArray());
        $eloquent->save();

        return $eloquent->toDomain();
    }

    public function findById(int $id): ?Bid
    {
        $eloquent = EloquentBid::find($id);

        return $eloquent?->toDomain();
    }

    public function findByUuid(string $uuid): ?Bid
    {
        $eloquent = EloquentBid::where('uuid', $uuid)->first();

        return $eloquent?->toDomain();
    }

    /** @return Collection<int, Bid> */
    public function findByAuction(int $auctionId): Collection
    {
        return EloquentBid::where('auction_id', $auctionId)
            ->orderBy('amount', 'desc')
            ->get()
            ->map(fn (EloquentBid $model) => $model->toDomain());
    }

    /** @return Collection<int, Bid> */
    public function findByBidder(int $bidderId): Collection
    {
        return EloquentBid::where('bidder_id', $bidderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (EloquentBid $model) => $model->toDomain());
    }

    /** @return Collection<int, Bid> */
    public function findByAuctionAndStatus(int $auctionId, string $status): Collection
    {
        return EloquentBid::where('auction_id', $auctionId)
            ->where('status', $status)
            ->orderBy('amount', 'desc')
            ->get()
            ->map(fn (EloquentBid $model) => $model->toDomain());
    }

    public function findHighest(int $auctionId): ?Bid
    {
        $eloquent = EloquentBid::where('auction_id', $auctionId)
            ->where('status', '!=', 'withdrawn')
            ->orderBy('amount', 'desc')
            ->first();

        return $eloquent?->toDomain();
    }

    public function updateStatus(int $id, string $status): bool
    {
        return EloquentBid::where('id', $id)->update(['status' => $status]) > 0;
    }

    public function delete(int $id): bool
    {
        return EloquentBid::where('id', $id)->delete() > 0;
    }
}
