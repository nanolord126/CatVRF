<?php

declare(strict_types=1);

namespace Modules\RealEstate\Domain\Enums;

enum ListingType: string
{
    case SALE = 'sale';
    case RENT = 'rent';
    case LEASE = 'lease';
    case AUCTION = 'auction';

    public function label(): string
    {
        return match ($this) {
            self::SALE => 'For Sale',
            self::RENT => 'For Rent',
            self::LEASE => 'For Lease',
            self::AUCTION => 'Auction',
        };
    }

    public function isForSale(): bool
    {
        return in_array($this, [self::SALE, self::AUCTION], true);
    }

    public function isForRent(): bool
    {
        return in_array($this, [self::RENT, self::LEASE], true);
    }
}
