<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Enums;

enum WarehouseTypeEnum: string
{
    case CENTRAL = 'central';
    case REGIONAL = 'regional';
    case SATELLITE = 'satellite';
    case CROSS_DOCK = 'cross_dock';
    case COLD_STORAGE = 'cold_storage';
    case FROZEN_STORAGE = 'frozen_storage';

    public function getLabel(): string
    {
        return match ($this) {
            self::CENTRAL => 'Central Warehouse',
            self::REGIONAL => 'Regional Warehouse',
            self::SATELLITE => 'Satellite Warehouse',
            self::CROSS_DOCK => 'Cross Dock',
            self::COLD_STORAGE => 'Cold Storage',
            self::FROZEN_STORAGE => 'Frozen Storage',
        };
    }

    public function getPriority(): int
    {
        return match ($this) {
            self::CENTRAL => 1,
            self::REGIONAL => 2,
            self::CROSS_DOCK => 3,
            self::COLD_STORAGE => 4,
            self::FROZEN_STORAGE => 5,
            self::SATELLITE => 6,
        };
    }

    public function requiresTemperatureControl(): bool
    {
        return $this === self::COLD_STORAGE || $this === self::FROZEN_STORAGE;
    }

    public function getDefaultTemperature(): ?int
    {
        return match ($this) {
            self::COLD_STORAGE => 4,
            self::FROZEN_STORAGE => -18,
            default => null,
        };
    }
}
