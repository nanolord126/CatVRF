<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Domain\ValueObjects;

use App\Shared\Domain\ValueObjects\ValueObject;
use Webmozart\Assert\Assert;

/**
 * Class Address
 *
 * Part of the Hotels vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Hotels\Domain\ValueObjects
 */
final class Address extends ValueObject
{
    public function __construct(
        private readonly string $country,
        private readonly string $city,
        private readonly string $street,
        private readonly string $houseNumber,
        private ?string $zipCode = null
    ) {
        Assert::notEmpty($country, 'Country cannot be empty.');
        Assert::notEmpty($city, 'City cannot be empty.');
        Assert::notEmpty($street, 'Street cannot be empty.');
        Assert::notEmpty($houseNumber, 'House number cannot be empty.');
    }

    public function getFullAddress(): string
    {
        return trim(sprintf(
            '%s, %s, %s, %s, %s',
            $this->zipCode,
            $this->country,
            $this->city,
            $this->street,
            $this->houseNumber
        ), ', ');
    }

    public function toArray(): array
    {
        return [
            'country' => $this->country,
            'city' => $this->city,
            'street' => $this->street,
            'house_number' => $this->houseNumber,
            'zip_code' => $this->zipCode,
        ];
    }

    protected function getEqualityComponents(): array
    {
        return [
            $this->country,
            $this->city,
            $this->street,
            $this->houseNumber,
            $this->zipCode,
        ];
    }
}
