<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Enums;

enum LoyaltyRuleType: string
{
    case ORDER_BASED = 'order_based';
    case VISIT_BASED = 'visit_based';
    case ITEM_BASED = 'item_based';
    case TIME_BASED = 'time_based';
    case FIRST_VISIT = 'first_visit';
    case BIRTHDAY = 'birthday';
    case REFERRAL = 'referral';
    case MILESTONE = 'milestone';
    case SOCIAL = 'social';
    case REVIEW = 'review';
    case CUSTOM = 'custom';
}
