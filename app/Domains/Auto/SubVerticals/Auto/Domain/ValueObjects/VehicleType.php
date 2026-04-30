<?php

declare(strict_types=1);

namespace Modules\Auto\Domain\ValueObjects;

final readonly class VehicleType
{
    public const SEDAN = 'sedan';
    public const SUV = 'suv';
    public const TRUCK = 'truck';
    public const VAN = 'van';
    public const MOTORCYCLE = 'motorcycle';
    public const ELECTRIC = 'electric';
    public const HYBRID = 'hybrid';

    private const VALID_TYPES = [
        self::SEDAN,
        self::SUV,
        self::TRUCK,
        self::VAN,
        self::MOTORCYCLE,
        self::ELECTRIC,
        self::HYBRID,
    ];

    public function __construct(
        public string $value,
    ) {
        if (!in_array($this->value, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid vehicle type: ' . $this->value);
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function sedan(): self
    {
        return new self(self::SEDAN);
    }

    public static function suv(): self
    {
        return new self(self::SUV);
    }

    public static function truck(): self
    {
        return new self(self::TRUCK);
    }

    public static function van(): self
    {
        return new self(self::VAN);
    }

    public static function motorcycle(): self
    {
        return new self(self::MOTORCYCLE);
    }

    public static function electric(): self
    {
        return new self(self::ELECTRIC);
    }

    public static function hybrid(): self
    {
        return new self(self::HYBRID);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
