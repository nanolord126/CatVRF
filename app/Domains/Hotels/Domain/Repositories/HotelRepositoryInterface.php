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


namespace App\Domains\Hotels\Domain\Repositories;

use App\Domains\Hotels\Domain\Entities\Hotel;
use App\Domains\Hotels\Domain\ValueObjects\HotelId;
use Illuminate\Support\Collection;

interface HotelRepositoryInterface
{
    public function find(HotelId $id): ?Hotel;

    public function findByTenant(int $tenantId): Collection;

    public function save(Hotel $hotel): void;

    public function delete(HotelId $id): bool;

    /**
     * @param array $criteria
     * @return Collection<Hotel>
     */
    public function search(array $criteria): Collection;
}
