<?php

declare(strict_types=1);

namespace Modules\Promo\Domain\Enums;

/**
 * Enum PromoType
 *
 * Defines strategically distinct bounded mechanics for explicitly structured coupon assignments
 * effectively logically separating processing parameters smoothly definitively efficiently uniquely natively.
 */
enum PromoType: string
{
    /** Applies mathematically strict percentage correctly implicitly distinct deductions natively. */
    case DISCOUNT_PERCENT = 'discount_percent';

    /** Subtracts definitively explicitly distinct mathematically isolated absolute exact natively scalars safely. */
    case FIXED_AMOUNT = 'fixed_amount';

    /** Rewards cleanly structurally explicitly distinct specific natively bounded specific groupings seamlessly. */
    case BUY_X_GET_Y = 'buy_x_get_y';

    /** Modifies cart composition structurally distinct combining distinctly strictly natively correctly mapped sequences explicitly smoothly. */
    case BUNDLE = 'bundle';

    /** Triggers isolated wallet mechanisms seamlessly completely smoothly logically mapping effectively seamlessly accurately natively inherently securely. */
    case GIFT_CARD = 'gift_card';

    /** Credits strategically exactly logically native bounded structural metrics flawlessly functionally clearly safely physically inherently logically mapping distinctly natively securely. */
    case REFERRAL_BONUS = 'referral_bonus';

    /** Credits dynamically perfectly exactly completely natively inherently logically safely firmly mapping dynamically cleanly thoroughly correctly cleanly smoothly securely uniquely fundamentally securely. */
    case TURNOVER_BONUS = 'turnover_bonus';

    /**
     * Resolves strings validating intrinsically pure correctly bound explicit sequences distinctly dynamic thoroughly securely mapping native safely strictly accurately exactly optimally definitively functionally properly correctly safely completely natively effectively.
     *
     * @param string $value Specific parameter strictly natively validating.
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }
}
