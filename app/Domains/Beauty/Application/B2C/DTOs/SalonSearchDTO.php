<?php

declare(strict_types=1);

/**
 * SalonSearchDTO — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/salonsearchdto
 */


namespace App\Domains\Beauty\Application\B2C\DTOs;

/**
 * DTO для поиска салонов клиентом (B2C).
 */
final readonly class SalonSearchDTO
{
    public function __construct(
        public int $tenantId,
        public ?string $category = null,
        public ?float $lat = null,
        public ?float $lon = null,
        public ?int $radiusKm = null,
        public ?int $maxPriceRub = null,
        public ?int $page = 1,
        public ?int $perPage = 20,
        public ?string $correlationId = null,
    ) {}

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::tenant:' . $this->tenantId;
    }
/**
     * Преобразование в массив для audit-лога и сериализации.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id'      => $this->tenantId,
            'category'       => $this->category,
            'lat'            => $this->lat,
            'lon'            => $this->lon,
            'radius_km'      => $this->radiusKm,
            'max_price_rub'  => $this->maxPriceRub,
            'page'           => $this->page,
            'per_page'       => $this->perPage,
            'correlation_id' => $this->correlationId,
        ];
    }
}
