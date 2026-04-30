<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Interfaces;

use App\Domains\Advertising\Domain\Entities\AdShort;
use Illuminate\Database\Eloquent\Collection;

/**
 * Ad Short Repository Interface
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
interface AdShortRepositoryInterface
{
    public function save(AdShort $adShort): AdShort;

    public function findById(int $id): ?AdShort;

    public function findByUuid(string $uuid): ?AdShort;

    /** @return Collection<int, AdShort> */
    public function findByTenant(int $tenantId): Collection;

    /** @return Collection<int, AdShort> */
    public function findActive(): Collection;

    /** @return Collection<int, AdShort> */
    public function findActiveByTenant(int $tenantId): Collection;

    public function updateSpent(int $id, int $spent): bool;

    public function updateStatus(int $id, string $status): bool;

    public function delete(int $id): bool;
}
