<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Enums;

enum MovementTypeEnum: string
{
    case RECEIPT = 'receipt';
    case TRANSFER = 'transfer';
    case PICKING = 'picking';
    case PACKING = 'packing';
    case SHIPMENT = 'shipment';
    case RETURN = 'return';
    case ADJUSTMENT = 'adjustment';
    case DAMAGE = 'damage';
    case LOSS = 'loss';
    case CONVERSION = 'conversion';

    public function getLabel(): string
    {
        return match ($this) {
            self::RECEIPT => 'Receipt',
            self::TRANSFER => 'Transfer',
            self::PICKING => 'Picking',
            self::PACKING => 'Packing',
            self::SHIPMENT => 'Shipment',
            self::RETURN => 'Return',
            self::ADJUSTMENT => 'Adjustment',
            self::DAMAGE => 'Damage',
            self::LOSS => 'Loss',
            self::CONVERSION => 'Conversion',
        };
    }

    public function increasesStock(): bool
    {
        return $this === self::RECEIPT || $this === self::ADJUSTMENT;
    }

    public function decreasesStock(): bool
    {
        return $this === self::SHIPMENT || $this === self::DAMAGE || $this === self::LOSS;
    }

    public function requiresOrderReference(): bool
    {
        return in_array($this, [self::PICKING, self::PACKING, self::SHIPMENT, self::RETURN], true);
    }

    public function isInternal(): bool
    {
        return in_array($this, [self::TRANSFER, self::ADJUSTMENT, self::CONVERSION], true);
    }
}
