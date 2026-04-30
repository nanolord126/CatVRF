<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Interfaces;

use App\Domains\Advertising\Domain\Entities\Auction;
use Illuminate\Database\Eloquent\Collection;

/**
 * Auction Repository Interface
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
interface AuctionRepositoryInterface
{
    public function save(Auction $auction): Auction;

    public function findById(int $id): ?Auction;

    public function findByUuid(string $uuid): ?Auction;

    /** @return Collection<int, Auction> */
    public function findByTenant(int $tenantId): Collection;

    /** @return Collection<int, Auction> */
    public function findActive(): Collection;

    /** @return Collection<int, Auction> */
    public function findByInventory(int $inventoryId): Collection;

    public function updateBidHistory(int $id, array $bidHistory): bool;

    public function updateCurrentPrice(int $id, int $price): bool;

    public function updateStatus(int $id, string $status): bool;

    public function delete(int $id): bool;
}
