<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Class Coordinates
 *
 * Part of the GeoLogistics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\GeoLogistics\Domain\ValueObjects
 */
final readonly class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude) {
        if ($this->latitude < -90.0 || $this->latitude > 90.0) {
            throw new InvalidArgumentException("Категорическое нарушение: широта должна быть между -90 и 90.");
        }
        if ($this->longitude < -180.0 || $this->longitude > 180.0) {
            throw new InvalidArgumentException("Категорическое нарушение: долгота должна быть между -180 и 180.");
        }
    }

    /**
     * Handle toArray operation.
     *
     * @throws \DomainException
     */
    public function toArray(): array
    {
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }
}
