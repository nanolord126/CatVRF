<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Enums;

enum EventCategory: string
{
    case USER_BEHAVIOR = 'user_behavior';
    case TRANSACTION = 'transaction';
    case ENGAGEMENT = 'engagement';
    case SYSTEM = 'system';
    case MARKETING = 'marketing';

    public function label(): string
    {
        return match ($this) {
            self::USER_BEHAVIOR => 'User Behavior',
            self::TRANSACTION => 'Transaction',
            self::ENGAGEMENT => 'Engagement',
            self::SYSTEM => 'System',
            self::MARKETING => 'Marketing',
        };
    }
}
