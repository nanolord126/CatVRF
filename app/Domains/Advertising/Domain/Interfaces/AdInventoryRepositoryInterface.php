<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Interfaces;

use App\Domains\Advertising\Domain\Entities\AdInventory;
use Illuminate\Database\Eloquent\Collection;

/**
 * Ad Inventory Repository Interface
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
interface AdInventoryRepositoryInterface
{
    public function save(AdInventory $inventory): AdInventory;

    public function findById(int $id): ?AdInventory;

    public function findByUuid(string $uuid): ?AdInventory;

    /** @return Collection<int, AdInventory> */
    public function findByPublisher(int $publisherId): Collection;

    /** @return Collection<int, AdInventory> */
    public function findAvailable(
        string $inventoryType,
        string $placement,
        array $targeting = []
    ): Collection;

    /** @return Collection<int, AdInventory> */
    public function findActive(): Collection;

    public function reserve(int $id, int $impressions): bool;

    public function release(int $id, int $impressions): bool;

    public function updateStatus(int $id, string $status): bool;

    public function delete(int $id): bool;
}
