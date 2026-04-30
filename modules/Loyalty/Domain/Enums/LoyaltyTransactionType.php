<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Enums;

enum LoyaltyTransactionType: string
{
    case EARNED = 'earned';
    case REDEEMED = 'redeemed';
    case BONUS = 'bonus';
    case EXPIRED = 'expired';
    case ADJUSTED = 'adjusted';
    case REFUNDED = 'refunded';
}
