<?php

declare(strict_types=1);

namespace Modules\RealEstate\Domain\ValueObjects;

final readonly class PropertyType
{
    public const APARTMENT = 'apartment';
    public const HOUSE = 'house';
    public const VILLA = 'villa';
    public const COMMERCIAL = 'commercial';
    public const LAND = 'land';
    public const STUDIO = 'studio';
    public const PENTHOUSE = 'penthouse';

    private const VALID_TYPES = [
        self::APARTMENT,
        self::HOUSE,
        self::VILLA,
        self::COMMERCIAL,
        self::LAND,
        self::STUDIO,
        self::PENTHOUSE,
    ];

    public function __construct(
        public string $value,
    ) {
        if (!in_array($this->value, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid property type: ' . $this->value);
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function apartment(): self
    {
        return new self(self::APARTMENT);
    }

    public static function house(): self
    {
        return new self(self::HOUSE);
    }

    public static function villa(): self
    {
        return new self(self::VILLA);
    }

    public static function commercial(): self
    {
        return new self(self::COMMERCIAL);
    }

    public static function land(): self
    {
        return new self(self::LAND);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
