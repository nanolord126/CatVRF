<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cooldown Status
 * 
 * Defines the possible states of a cooldown period.
 */
enum CooldownStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case OVERRIDDEN = 'overridden';

    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Активен',
            self::EXPIRED => 'Истёк',
            self::OVERRIDDEN => 'Отменён администратором',
        };
    }

    /**
     * Check if cooldown is currently blocking actions
     */
    public function isBlocking(): bool
    {
        return $this === self::ACTIVE;
    }
}
