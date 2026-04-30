<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Bot Risk Level
 * 
 * Defines the risk levels for bot traffic detection.
 * Each level has corresponding protection measures.
 */
enum BotRiskLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    /**
     * Get cooldown duration in hours for this risk level
     */
    public function getCooldownHours(): int
    {
        return match ($this) {
            self::LOW => 0,
            self::MEDIUM => 1,
            self::HIGH => 24,
            self::CRITICAL => 168, // 7 days
        };
    }

    /**
     * Check if this risk level requires blocking
     */
    public function requiresBlocking(): bool
    {
        return in_array($this, [self::HIGH, self::CRITICAL], true);
    }

    /**
     * Check if this risk level requires challenge (Turnstile/CAPTCHA)
     */
    public function requiresChallenge(): bool
    {
        return in_array($this, [self::MEDIUM, self::HIGH], true);
    }

    /**
     * Check if this risk level requires split key invalidation
     */
    public function requiresSplitKeyInvalidation(): bool
    {
        return $this === self::CRITICAL;
    }

    /**
     * Check if this risk level should trigger notifications to owners
     */
    public function requiresOwnerNotification(): bool
    {
        return in_array($this, [self::HIGH, self::CRITICAL], true);
    }

    /**
     * Check if this risk level should trigger alerts to super-admins
     */
    public function requiresSuperAdminAlert(): bool
    {
        return $this === self::CRITICAL;
    }

    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::LOW => 'Низкий риск',
            self::MEDIUM => 'Средний риск',
            self::HIGH => 'Высокий риск',
            self::CRITICAL => 'Критический риск',
        };
    }

    /**
     * Get description of protection measures
     */
    public function getProtectionDescription(): string
    {
        return match ($this) {
            self::LOW => 'Разрешить с логированием',
            self::MEDIUM => 'Managed Challenge + rate limiting',
            self::HIGH => 'Блокировка + Cooldown 24ч + уведомление owner',
            self::CRITICAL => 'Постоянная блокировка + Cooldown 7 дней + уведомление всех owner и super-admin',
        };
    }

    /**
     * Get numeric score for ML models (0-1)
     */
    public function getScore(): float
    {
        return match ($this) {
            self::LOW => 0.0,
            self::MEDIUM => 0.33,
            self::HIGH => 0.66,
            self::CRITICAL => 1.0,
        };
    }
}
