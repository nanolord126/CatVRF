<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Enums;

/**
 * Enum BatchStatus
 *
 * Defines the status of a locked bonus batch.
 */
enum BatchStatus: string
{
    case LOCKED = 'locked';
    case VESTING = 'vesting';
    case UNLOCKED = 'unlocked';
    case SOLD = 'sold';

    /**
     * Checks if the batch is currently locked.
     */
    public function isLocked(): bool
    {
        return match ($this) {
            self::LOCKED, self::VESTING => true,
            self::UNLOCKED, self::SOLD => false,
        };
    }

    /**
     * Checks if the batch is fully available.
     */
    public function isAvailable(): bool
    {
        return $this === self::UNLOCKED;
    }

    /**
     * Checks if the batch can be sold.
     */
    public function canBeSold(): bool
    {
        return match ($this) {
            self::LOCKED, self::VESTING => true,
            self::UNLOCKED, self::SOLD => false,
        };
    }

    /**
     * Creates batch status from string.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::LOCKED;
    }
}
