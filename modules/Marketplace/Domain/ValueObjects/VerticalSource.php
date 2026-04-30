<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\ValueObjects;

/**
 * Источник витринной позиции (вертикаль)
 */
enum VerticalSource: string
{
    case BEAUTY = 'beauty';
    case RESTAURANT = 'restaurant';
    case FASHION = 'fashion';
    case HOTELS = 'hotels';
    case FITNESS = 'fitness';
    case FLOWERS = 'flowers';
    case DENTAL = 'dental';
    case VET_GROOMING = 'vet_grooming';
    case TAXI = 'taxi';
    case REAL_ESTATE = 'real_estate';
    case SUPERMARKET = 'supermarket';
    case AUTO = 'auto';
    case MEDIA = 'media';
    case VIDEO = 'video';
    case CUSTOM = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::BEAUTY => 'Beauty Masters',
            self::RESTAURANT => 'Restaurant',
            self::FASHION => 'Fashion',
            self::HOTELS => 'Hotels',
            self::FITNESS => 'Fitness',
            self::FLOWERS => 'Flowers',
            self::DENTAL => 'Dental',
            self::VET_GROOMING => 'Vet Grooming',
            self::TAXI => 'Taxi',
            self::REAL_ESTATE => 'Real Estate',
            self::SUPERMARKET => 'Supermarket',
            self::AUTO => 'Auto',
            self::MEDIA => 'Media',
            self::VIDEO => 'Video',
            self::CUSTOM => 'Custom',
        };
    }

    public function getPriority(): int
    {
        return match ($this) {
            self::RESTAURANT, self::BEAUTY, self::FASHION => 1,
            self::HOTELS, self::FITNESS => 2,
            self::FLOWERS, self::DENTAL, self::VET_GROOMING => 3,
            self::TAXI, self::REAL_ESTATE => 4,
            self::SUPERMARKET, self::AUTO => 5,
            self::MEDIA, self::VIDEO => 6,
            self::CUSTOM => 10,
        };
    }

    public function supportsRealTimeInventory(): bool
    {
        return in_array($this, [self::RESTAURANT, self::BEAUTY, self::FLOWERS, self::SUPERMARKET], true);
    }

    public function supportsBooking(): bool
    {
        return in_array($this, [self::BEAUTY, self::RESTAURANT, self::HOTELS, self::FITNESS, self::DENTAL, self::VET_GROOMING], true);
    }

    public function supportsDelivery(): bool
    {
        return in_array($this, [self::RESTAURANT, self::FLOWERS, self::SUPERMARKET, self::FASHION], true);
    }
}
