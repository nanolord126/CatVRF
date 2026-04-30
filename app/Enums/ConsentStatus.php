<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Consent Status for 152-FZ Compliance
 * 
 * Tracks the lifecycle of user consent for personal data processing.
 */
enum ConsentStatus: string
{
    case GRANTED = 'granted';
    case WITHDRAWN = 'withdrawn';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';

    /**
     * Check if consent is currently active
     */
    public function isActive(): bool
    {
        return $this === self::GRANTED;
    }

    /**
     * Check if consent was terminated by user action
     */
    public function isUserTerminated(): bool
    {
        return $this === self::WITHDRAWN || $this === self::REVOKED;
    }

    /**
     * Get human-readable label in Russian
     */
    public function label(): string
    {
        return match ($this) {
            self::GRANTED => 'Предоставлено',
            self::WITHDRAWN => 'Отозвано',
            self::EXPIRED => 'Истекло',
            self::REVOKED => 'Аннулировано',
        };
    }

    /**
     * Get CSS color class for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::GRANTED => 'green',
            self::WITHDRAWN => 'red',
            self::EXPIRED => 'yellow',
            self::REVOKED => 'red',
        };
    }
}
