<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Residential Proxy Risk Level Enum
 *
 * Specific risk levels for residential proxy detection.
 * More granular than VpnRiskLevel for residential proxy scenarios.
 */
enum ResidentialProxyRiskLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';
    case PERMANENT_BLOCK = 'permanent_block';

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
            self::PERMANENT_BLOCK => 'Постоянная блокировка',
        };
    }

    /**
     * Get cooldown duration in hours for this risk level
     * 
     * Gradient:
     * - LOW: No cooldown (whitelisted, ethical residential)
     * - MEDIUM: 12 hours (medium confidence, known medium-risk provider)
     * - HIGH: 72 hours (high confidence, behavioral anomaly, geo mismatch)
     * - CRITICAL: 168 hours (7 days) (high confidence + behavioral anomaly + sensitive action)
     * - PERMANENT_BLOCK: 0 (permanent block, no cooldown)
     */
    public function getCooldownHours(): int
    {
        return match ($this) {
            self::LOW => 0,
            self::MEDIUM => 12,
            self::HIGH => 72, // 72 hours for high risk
            self::CRITICAL => 168, // 7 days for critical risk
            self::PERMANENT_BLOCK => 0, // Permanent block doesn't use cooldown
        };
    }

    /**
     * Get corresponding VpnRiskLevel
     */
    public function toVpnRiskLevel(): VpnRiskLevel
    {
        return match ($this) {
            self::LOW => VpnRiskLevel::LOW,
            self::MEDIUM => VpnRiskLevel::MEDIUM,
            self::HIGH => VpnRiskLevel::HIGH,
            self::CRITICAL => VpnRiskLevel::CRITICAL,
            self::PERMANENT_BLOCK => VpnRiskLevel::CRITICAL,
        };
    }

    /**
     * Check if this risk level requires split key invalidation
     */
    public function requiresSplitKeyInvalidation(): bool
    {
        return $this === self::HIGH || $this === self::CRITICAL || $this === self::PERMANENT_BLOCK;
    }

    /**
     * Check if this risk level requires manual review
     */
    public function requiresManualReview(): bool
    {
        return $this === self::HIGH || $this === self::CRITICAL || $this === self::PERMANENT_BLOCK;
    }

    /**
     * Check if this risk level should trigger notifications to tenant owners
     */
    public function shouldNotifyTenantOwners(): bool
    {
        return $this === self::MEDIUM || $this === self::HIGH || $this === self::CRITICAL || $this === self::PERMANENT_BLOCK;
    }

    /**
     * Check if this risk level should trigger notifications to super-admin
     */
    public function shouldNotifySuperAdmin(): bool
    {
        return $this === self::CRITICAL || $this === self::PERMANENT_BLOCK;
    }

    /**
     * Check if this risk level requires permanent IP blacklist
     */
    public function requiresPermanentBlacklist(): bool
    {
        return $this === self::PERMANENT_BLOCK;
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
            self::CRITICAL => 90,
            self::PERMANENT_BLOCK => 100,
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
            self::PERMANENT_BLOCK => 'purple',
        };
    }

    /**
     * Get protection measures description
     */
    public function getProtectionMeasures(): array
    {
        return match ($this) {
            self::LOW => [
                'Log detection',
                'Light rate limiting',
            ],
            self::MEDIUM => [
                'Managed Challenge (Turnstile)',
                'Cooldown 12-24 hours',
                'Increased monitoring',
            ],
            self::HIGH => [
                'Block for 24-72 hours',
                'Invalidate SplitKey',
                'Notify tenant owners',
                'Manual review required',
            ],
            self::CRITICAL => [
                'Block for 72+ hours',
                'Invalidate SplitKey',
                'Logout all sessions',
                'Notify tenant owners',
                'Notify super-admin',
                'Manual review required',
            ],
            self::PERMANENT_BLOCK => [
                'Permanent IP/User block',
                'Invalidate SplitKey',
                'Logout all sessions',
                'Add to blacklist',
                'Notify super-admin',
                'Manual review required',
            ],
        };
    }
}
