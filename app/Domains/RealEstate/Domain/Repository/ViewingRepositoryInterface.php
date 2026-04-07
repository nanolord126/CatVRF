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


namespace App\Domains\RealEstate\Domain\Repository;

use App\Domains\RealEstate\Domain\Entities\ViewingAppointment;
use App\Domains\RealEstate\Domain\Enums\ViewingStatusEnum;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use App\Domains\RealEstate\Domain\ValueObjects\ViewingId;
use Illuminate\Support\Collection;

interface ViewingRepositoryInterface
{
    public function findById(ViewingId $id): ?ViewingAppointment;

    public function findByIdAndTenant(ViewingId $id, int $tenantId): ?ViewingAppointment;

    /**
     * @return Collection<int, ViewingAppointment>
     */
    public function findByPropertyId(PropertyId $propertyId): Collection;

    /**
     * @return Collection<int, ViewingAppointment>
     */
    public function findByTenantAndStatus(int $tenantId, ViewingStatusEnum $status): Collection;

    /**
     * @return Collection<int, ViewingAppointment>
     */
    public function findByTenantId(int $tenantId): Collection;

    /**
     * Check for scheduling conflicts on the same property at the same time.
     */
    public function hasConflict(
        PropertyId         $propertyId,
        \DateTimeImmutable $scheduledAt,
        ?ViewingId         $excludeId = null,
    ): bool;

    public function save(ViewingAppointment $viewing): void;
}
