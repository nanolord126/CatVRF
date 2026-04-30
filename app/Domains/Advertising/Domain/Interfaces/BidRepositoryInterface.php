<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Interfaces;

use App\Domains\Advertising\Domain\Entities\Bid;
use Illuminate\Database\Eloquent\Collection;

/**
 * Bid Repository Interface
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
interface BidRepositoryInterface
{
    public function save(Bid $bid): Bid;

    public function findById(int $id): ?Bid;

    public function findByUuid(string $uuid): ?Bid;

    /** @return Collection<int, Bid> */
    public function findByAuction(int $auctionId): Collection;

    /** @return Collection<int, Bid> */
    public function findByBidder(int $bidderId): Collection;

    /** @return Collection<int, Bid> */
    public function findByAuctionAndStatus(int $auctionId, string $status): Collection;

    public function findHighest(int $auctionId): ?Bid;

    public function updateStatus(int $id, string $status): bool;

    public function delete(int $id): bool;
}
