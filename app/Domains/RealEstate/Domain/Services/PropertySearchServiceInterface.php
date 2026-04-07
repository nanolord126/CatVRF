<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Domain\Services;

use App\Domains\RealEstate\Domain\Entities\Property;
use App\Domains\RealEstate\Domain\Enums\PropertyTypeEnum;
use Illuminate\Support\Collection;

/**
 * Domain service interface for property search and geo-filtering.
 */
interface PropertySearchServiceInterface
{
    /**
     * Execute a full-text + geo search over active properties.
     *
     * @return Collection<int, Property>
     */
    public function search(
        ?string           $query,
        ?PropertyTypeEnum $type,
        ?int              $minPriceKopecks,
        ?int              $maxPriceKopecks,
        ?float            $minAreaSqm,
        ?int              $rooms,
        ?float            $lat,
        ?float            $lon,
        ?int              $radiusMeters,
        int               $tenantId,
        int               $perPage,
        int               $page,
    ): Collection;

    /**
     * Count results for pagination metadata.
     */
    public function count(
        ?string           $query,
        ?PropertyTypeEnum $type,
        ?int              $minPriceKopecks,
        ?int              $maxPriceKopecks,
        ?float            $minAreaSqm,
        ?int              $rooms,
        ?float            $lat,
        ?float            $lon,
        ?int              $radiusMeters,
        int               $tenantId,
    ): int;

    /**
     * Find properties near a coordinate within a given radius (metres).
     *
     * @return Collection<int, Property>
     */
    public function findNearby(
        float $lat,
        float $lon,
        int   $radiusMeters,
        int   $tenantId,
        int   $limit = 10,
    ): Collection;
}
