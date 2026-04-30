<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Enums;

/**
 * Enum VestingCurveType
 *
 * Defines the type of vesting curve for locked bonus batches.
 * Linear: Equal daily release (6.67% per day for 15 days)
 * Accelerated: Faster release based on activity/streak
 * Custom: Custom schedule defined in vesting_schedule JSON
 */
enum VestingCurveType: string
{
    case LINEAR = 'linear';
    case ACCELERATED = 'accelerated';
    case CUSTOM = 'custom';

    /**
     * Gets the daily release percentage for the vesting curve.
     */
    public function getDailyReleasePercentage(): float
    {
        return match ($this) {
            self::LINEAR => 6.67, // 100% / 15 days
            self::ACCELERATED => 10.0, // Faster release
            self::CUSTOM => 0.0, // Calculated from schedule
        };
    }

    /**
     * Checks if this curve type supports acceleration.
     */
    public function supportsAcceleration(): bool
    {
        return match ($this) {
            self::LINEAR, self::ACCELERATED => true,
            self::CUSTOM => false,
        };
    }

    /**
     * Creates vesting curve type from string.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::LINEAR;
    }
}
