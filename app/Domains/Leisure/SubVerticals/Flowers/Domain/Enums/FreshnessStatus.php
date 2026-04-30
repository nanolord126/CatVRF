<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Enums;

enum FreshnessStatus: string
{
    case FRESH = 'fresh';
    case GOOD = 'good';
    case AGING = 'aging';
    case EXPIRING_SOON = 'expiring_soon';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::FRESH => 'Свежие',
            self::GOOD => 'Хорошее состояние',
            self::AGING => 'Стареют',
            self::EXPIRING_SOON => 'Скоро испортятся',
            self::EXPIRED => 'Испорчены',
        };
    }

    public function warningLevel(): string
    {
        return match ($this) {
            self::FRESH, self::GOOD => 'none',
            self::AGING => 'info',
            self::EXPIRING_SOON => 'warning',
            self::EXPIRED => 'danger',
        };
    }

    public function canBeUsed(): bool
    {
        return in_array($this, [self::FRESH, self::GOOD, self::AGING]);
    }
}
