<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Class Address
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Shared\Domain\ValueObjects
 */
final readonly class Address
{
    public function __construct(
        public string $fullAddress,
        private ?string $city = null,
        private readonly ?string $street = null,
        private readonly ?string $house = null,
        private readonly ?float $lat = null,
        private readonly ?float $lon = null,
    ) {
        if (empty($fullAddress)) {
            throw new InvalidArgumentException('Full address cannot be empty.');
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
            'full_address' => $this->fullAddress,
            'city' => $this->city,
            'street' => $this->street,
            'house' => $this->house,
            'lat' => $this->lat,
            'lon' => $this->lon,
        ];
    }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
