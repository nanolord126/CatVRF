<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Enums;

enum LoyaltyRewardType: string
{
    case DISCOUNT = 'discount';
    case FREE_ITEM = 'free_item';
    case UPGRADE = 'upgrade';
    case SERVICE = 'service';
    case CASHBACK = 'cashback';
    case VOUCHER = 'voucher';
    case PRIVILEGE = 'privilege';
    case CUSTOM = 'custom';
}
