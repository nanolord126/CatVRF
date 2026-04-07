<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Domain\Repository;

use App\Domains\RealEstate\Domain\Entities\Property;
use App\Domains\RealEstate\Domain\Enums\PropertyStatusEnum;
use App\Domains\RealEstate\Domain\Enums\PropertyTypeEnum;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use Illuminate\Support\Collection;

interface PropertyRepositoryInterface
{
    public function findById(PropertyId $id): ?Property;

    public function findByIdAndTenant(PropertyId $id, int $tenantId): ?Property;

    /**
     * @return Collection<int, Property>
     */
    public function findByTenantId(int $tenantId): Collection;

    /**
     * @return Collection<int, Property>
     */
    public function findByStatus(PropertyStatusEnum $status, int $tenantId): Collection;

    /**
     * @return Collection<int, Property>
     */
    public function searchPublic(
        ?PropertyTypeEnum $type,
        ?int              $minPriceKopecks,
        ?int              $maxPriceKopecks,
        ?float            $minArea,
        ?int              $rooms,
        ?float            $lat,
        ?float            $lon,
        ?int              $radiusMeters,
        int               $perPage,
        int               $page,
    ): Collection;

    public function countSearchPublic(
        ?PropertyTypeEnum $type,
        ?int              $minPriceKopecks,
        ?int              $maxPriceKopecks,
        ?float            $minArea,
        ?int              $rooms,
        ?float            $lat,
        ?float            $lon,
        ?int              $radiusMeters,
    ): int;

    public function save(Property $property): void;

    public function delete(PropertyId $id): void;
}
