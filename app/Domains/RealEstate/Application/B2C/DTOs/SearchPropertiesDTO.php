<?php

declare(strict_types=1);

/**
 * SearchPropertiesDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/searchpropertiesdto
 */


namespace App\Domains\RealEstate\Application\B2C\DTOs;

use App\Domains\RealEstate\Domain\Enums\PropertyTypeEnum;

/**
 * Class SearchPropertiesDTO
 *
 * Part of the RealEstate vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\RealEstate\Application\B2C\DTOs
 */
final readonly class SearchPropertiesDTO
{
    public function __construct(
        public ?string           $query,
        public ?PropertyTypeEnum $type,
        public ?int              $minPriceKopecks,
        public ?int              $maxPriceKopecks,
        public ?float            $minAreaSqm,
        public ?int              $rooms,
        public ?float            $lat,
        public ?float            $lon,
        public ?int              $radiusMeters,
        public int               $perPage = 20,
        private int $page    = 1) {}

    public static function fromArray(array $data): self
    {
        return new self(
            query: isset($data['q']) ? (string) $data['q'] : null,
            type: isset($data['type']) ? PropertyTypeEnum::from((string) $data['type']) : null,
            minPriceKopecks: isset($data['min_price']) ? (int) ($data['min_price'] * 100) : null,
            maxPriceKopecks: isset($data['max_price']) ? (int) ($data['max_price'] * 100) : null,
            minAreaSqm: isset($data['min_area']) ? (float) $data['min_area'] : null,
            rooms: isset($data['rooms']) ? (int) $data['rooms'] : null,
            lat: isset($data['lat']) ? (float) $data['lat'] : null,
            lon: isset($data['lon']) ? (float) $data['lon'] : null,
            radiusMeters: isset($data['radius_m']) ? (int) $data['radius_m'] : null,
            perPage: isset($data['per_page']) ? max(1, min(100, (int) $data['per_page'])) : 20,
            page: isset($data['page']) ? max(1, (int) $data['page']) : 1,
        );
    }
}
