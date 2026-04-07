<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Enums;

/**
 * Enum BonusType
 *
 * Exposes core strategic categorization sequences distinguishing fundamental bonus allocations.
 * Enables segmented analytics aggregating and specific mapping rules restricting consumption.
 */
enum BonusType: string
{
    /**
     * Loyalty bounds accrued iteratively through progressive conventional purchasing sequences.
     */
    case LOYALTY = 'loyalty';

    /**
     * Allocations derived explicitly verifying inbound referral invitation tracking mechanics.
     */
    case REFERRAL = 'referral';

    /**
     * Compensatory sequences deployed mitigating customer service incidents and anomalies.
     */
    case COMPENSATION = 'compensation';

    /**
     * Highly restricted temporally bound promotional inputs driving marketing engagement campaigns.
     */
    case PROMOTIONAL = 'promotional';

    /**
     * Verification wrapper strictly enforcing valid transitions safely validating arrays strings.
     *
     * @param string $value Intrinsic raw mapping parameter input string.
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }

    /**
     * Transforms structured strings retrieving matching bound parameters dynamically.
     *
     * @param string $value Target string parameter filtering enumerations.
     * @return self|null
     */
    public static function fromString(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
