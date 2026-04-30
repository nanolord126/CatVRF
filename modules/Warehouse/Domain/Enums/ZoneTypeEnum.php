<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Enums;

enum ZoneTypeEnum: string
{
    case RECEIVING = 'receiving';
    case STORAGE = 'storage';
    case PICKING = 'picking';
    case PACKING = 'packing';
    case SHIPPING = 'shipping';
    case RETURNS = 'returns';
    case QUALITY_CONTROL = 'quality_control';
    case QUARANTINE = 'quarantine';

    public function getLabel(): string
    {
        return match ($this) {
            self::RECEIVING => 'Receiving Zone',
            self::STORAGE => 'Storage Zone',
            self::PICKING => 'Picking Zone',
            self::PACKING => 'Packing Zone',
            self::SHIPPING => 'Shipping Zone',
            self::RETURNS => 'Returns Zone',
            self::QUALITY_CONTROL => 'Quality Control Zone',
            self::QUARANTINE => 'Quarantine Zone',
        };
    }

    public function getProcessingOrder(): int
    {
        return match ($this) {
            self::RECEIVING => 1,
            self::QUALITY_CONTROL => 2,
            self::QUARANTINE => 3,
            self::STORAGE => 4,
            self::PICKING => 5,
            self::PACKING => 6,
            self::SHIPPING => 7,
            self::RETURNS => 8,
        };
    }

    public function allowsStockStorage(): bool
    {
        return $this === self::STORAGE || $this === self::QUARANTINE;
    }

    public function requiresSpecialHandling(): bool
    {
        return $this === self::QUALITY_CONTROL || $this === self::QUARANTINE || $this === self::RETURNS;
    }
}
