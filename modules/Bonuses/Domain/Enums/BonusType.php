<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Enums;

use InvalidArgumentException;

/**
 * Enum BonusType
 *
 * Exposes core strategic categorization sequences distinguishing fundamental bonus allocations.
 * Enables segmented analytics aggregating and specific mapping rules restricting consumption.
 * All bonus types have specific business rules, expiration policies, and consumption restrictions.
 */
enum BonusType: string
{
    /**
     * Loyalty bounds accrued iteratively through progressive conventional purchasing sequences.
     * Typically has longer expiration periods (6-12 months) and can be used across all verticals.
     */
    case LOYALTY = 'loyalty';

    /**
     * Allocations derived explicitly verifying inbound referral invitation tracking mechanics.
     * Requires referral to complete onboarding and minimum turnover threshold.
     */
    case REFERRAL = 'referral';

    /**
     * Compensatory sequences deployed mitigating customer service incidents and anomalies.
     * Manually awarded by support with strict approval workflows and audit trails.
     */
    case COMPENSATION = 'compensation';

    /**
     * Highly restricted temporally bound promotional inputs driving marketing engagement campaigns.
     * Short expiration (7-30 days), limited to specific verticals or services.
     */
    case PROMOTIONAL = 'promotional';

    /**
     * Turnover-based rewards for high-volume sellers or service providers.
     * Calculated based on monthly/quarterly revenue milestones.
     */
    case TURNOVER = 'turnover';

    /**
     * Special bonuses for completing specific actions (surveys, reviews, onboarding).
     * One-time awards with predefined expiration policies.
     */
    case ACTION = 'action';

    /**
     * Verification wrapper strictly enforcing valid transitions safely validating arrays strings.
     *
     * @param  string  $value  Intrinsic raw mapping parameter input string.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }

    /**
     * Transforms structured strings retrieving matching bound parameters dynamically.
     *
     * @param  string  $value  Target string parameter filtering enumerations.
     * @throws InvalidArgumentException When value is invalid.
     */
    public static function fromString(string $value): self
    {
        $type = self::tryFrom($value);
        
        if ($type === null) {
            throw new InvalidArgumentException(
                sprintf('Invalid bonus type: %s. Valid types are: %s', 
                    $value,
                    implode(', ', array_column(self::cases(), 'value'))
                )
            );
        }
        
        return $type;
    }

    /**
     * Returns default expiration period in days for this bonus type.
     * Returns null for bonuses that don't expire by default.
     */
    public function getDefaultExpirationDays(): ?int
    {
        return match ($this) {
            self::LOYALTY => 365,
            self::REFERRAL => 180,
            self::COMPENSATION => 90,
            self::PROMOTIONAL => 30,
            self::TURNOVER => 60,
            self::ACTION => 14,
        };
    }

    /**
     * Determines if this bonus type can be consumed across all verticals.
     * Some bonuses are restricted to specific verticals or services.
     */
    public function isUniversal(): bool
    {
        return match ($this) {
            self::LOYALTY, self::COMPENSATION => true,
            self::REFERRAL, self::PROMOTIONAL, self::TURNOVER, self::ACTION => false,
        };
    }

    /**
     * Returns maximum allowed amount for this bonus type in smallest currency unit.
     * This is a safety limit to prevent accidental massive awards.
     */
    public function getMaxAmount(): int
    {
        return match ($this) {
            self::LOYALTY => 50000000, // 500,000.00
            self::REFERRAL => 10000000, // 100,000.00
            self::COMPENSATION => 20000000, // 200,000.00
            self::PROMOTIONAL => 5000000, // 50,000.00
            self::TURNOVER => 100000000, // 1,000,000.00
            self::ACTION => 1000000, // 10,000.00
        };
    }

    /**
     * Checks if this bonus type requires additional verification before awarding.
     * For example, referral bonuses require referral completion validation.
     */
    public function requiresVerification(): bool
    {
        return match ($this) {
            self::REFERRAL, self::TURNOVER => true,
            self::LOYALTY, self::COMPENSATION, self::PROMOTIONAL, self::ACTION => false,
        };
    }

    /**
     * Returns the priority for consumption ordering.
     * Lower numbers are consumed first (FIFO for bonus consumption).
     */
    public function getConsumptionPriority(): int
    {
        return match ($this) {
            self::PROMOTIONAL => 1, // Consume promotional bonuses first (shortest expiration)
            self::ACTION => 2,
            self::REFERRAL => 3,
            self::TURNOVER => 4,
            self::COMPENSATION => 5,
            self::LOYALTY => 6, // Consume loyalty bonuses last (longest expiration)
        };
    }

    /**
     * Returns array of all valid bonus type values for validation.
     */
    public static function getValidValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Checks if this bonus type can be partially consumed.
     * Some bonuses must be consumed in full or not at all.
     */
    public function allowsPartialConsumption(): bool
    {
        return match ($this) {
            self::ACTION => false, // Action bonuses typically all-or-nothing
            self::LOYALTY, self::REFERRAL, self::COMPENSATION, self::PROMOTIONAL, self::TURNOVER => true,
        };
    }
}
