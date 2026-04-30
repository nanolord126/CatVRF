<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Enums;

enum SubscriptionFrequency: string
{
    case WEEKLY = 'weekly';
    case BIWEEKLY = 'biweekly';
    case MONTHLY = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::WEEKLY => 'Еженедельно',
            self::BIWEEKLY => 'Раз в 2 недели',
            self::MONTHLY => 'Ежемесячно',
        };
    }

    public function getDays(): int
    {
        return match ($this) {
            self::WEEKLY => 7,
            self::BIWEEKLY => 14,
            self::MONTHLY => 30,
        };
    }
}
