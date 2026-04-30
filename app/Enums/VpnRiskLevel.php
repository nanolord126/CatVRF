<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * VPN Risk Level
 *
 * Defines the risk levels for VPN/proxy detection.
 * Each level corresponds to specific protection measures.
 */
enum VpnRiskLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::LOW => 'Низкий',
            self::MEDIUM => 'Средний',
            self::HIGH => 'Высокий',
            self::CRITICAL => 'Критический',
        };
    }

    /**
     * Get cooldown duration in hours for this risk level
     * 
     * Gradient:
     * - LOW: No cooldown (corporate VPN, whitelisted)
     * - MEDIUM: 12 hours (commercial VPN without red flags)
     * - HIGH: 48-72 hours (residential proxy, VPN + geo-mismatch, behavioral anomaly)
     * - CRITICAL: 168 hours (7 days) (Tor + mass actions + high fraud score)
     */
    public function getCooldownHours(): int
    {
        return match ($this) {
            self::LOW => 0,
            self::MEDIUM => 12,
            self::HIGH => 72, // 72 hours for high risk (residential proxy, geo-mismatch)
            self::CRITICAL => 168, // 7 days for critical risk (Tor + mass actions)
        };
    }

    /**
     * Check if this risk level requires split key invalidation
     */
    public function requiresSplitKeyInvalidation(): bool
    {
        return $this === self::HIGH || $this === self::CRITICAL;
    }

    /**
     * Check if this risk level requires manual review
     */
    public function requiresManualReview(): bool
    {
        return $this === self::HIGH || $this === self::CRITICAL;
    }

    /**
     * Check if this risk level should trigger notifications to tenant owners
     */
    public function shouldNotifyTenantOwners(): bool
    {
        return $this === self::MEDIUM || $this === self::HIGH || $this === self::CRITICAL;
    }

    /**
     * Check if this risk level should trigger notifications to all owners and investors
     */
    public function shouldNotifyAllStakeholders(): bool
    {
        return $this === self::HIGH || $this === self::CRITICAL;
    }

    /**
     * Get numeric score for comparison (0-100)
     */
    public function getScore(): int
    {
        return match ($this) {
            self::LOW => 25,
            self::MEDIUM => 50,
            self::HIGH => 75,
            self::CRITICAL => 100,
        };
    }

    /**
     * Get color code for UI
     */
    public function getColor(): string
    {
        return match ($this) {
            self::LOW => 'green',
            self::MEDIUM => 'yellow',
            self::HIGH => 'orange',
            self::CRITICAL => 'red',
        };
    }
}
