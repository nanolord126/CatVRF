<?php

declare(strict_types=1);

namespace Modules\Auto\Domain\Enums;

enum FuelType: string
{
    case GASOLINE = 'gasoline';
    case DIESEL = 'diesel';
    case ELECTRIC = 'electric';
    case HYBRID = 'hybrid';
    case NATURAL_GAS = 'natural_gas';
    case HYDROGEN = 'hydrogen';

    public function label(): string
    {
        return match ($this) {
            self::GASOLINE => 'Gasoline',
            self::DIESEL => 'Diesel',
            self::ELECTRIC => 'Electric',
            self::HYBRID => 'Hybrid',
            self::NATURAL_GAS => 'Natural Gas',
            self::HYDROGEN => 'Hydrogen',
        };
    }

    public function isEnvironmentallyFriendly(): bool
    {
        return in_array($this, [self::ELECTRIC, self::HYBRID, self::HYDROGEN], true);
    }
}
