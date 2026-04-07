<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\Food\Domain\Repositories;

use App\Domains\Food\Domain\Entities\Restaurant;
use App\Shared\Domain\ValueObjects\TenantId;
use App\Shared\Domain\ValueObjects\Uuid;
use Illuminate\Support\Collection;

interface RestaurantRepositoryInterface
{
    public function findById(Uuid $id): ?Restaurant;

    public function findByTenant(TenantId $tenantId): Collection;

    public function save(Restaurant $restaurant): void;

    public function delete(Uuid $id): bool;

    /**
     * @param array<string, mixed> $criteria
     * @return Collection<Restaurant>
     */
    public function search(array $criteria): Collection;
}
