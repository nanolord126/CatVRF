<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\Enums;

/**
 * Типы витринных позиций маркетплейса
 */
enum ListingType: string
{
    case PRODUCT = 'product';
    case SERVICE = 'service';
    case BOOKING = 'booking';
    case SUBSCRIPTION = 'subscription';
    case DIGITAL = 'digital';
    case BUNDLE = 'bundle';
    case EXPERIENCE = 'experience';

    public function isPhysical(): bool
    {
        return in_array($this, [self::PRODUCT, self::BUNDLE], true);
    }

    public function isService(): bool
    {
        return in_array($this, [self::SERVICE, self::BOOKING, self::EXPERIENCE], true);
    }

    public function isDigital(): bool
    {
        return in_array($this, [self::DIGITAL, self::SUBSCRIPTION], true);
    }

    public function requiresInventory(): bool
    {
        return in_array($this, [self::PRODUCT, self::BUNDLE], true);
    }

    public function requiresScheduling(): bool
    {
        return in_array($this, [self::SERVICE, self::BOOKING, self::EXPERIENCE], true);
    }

    public function requiresStockTracking(): bool
    {
        return $this->requiresInventory() || $this === self::SERVICE;
    }
}
