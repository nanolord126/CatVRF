<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Proxy Type Enum
 *
 * Defines the types of proxies that can be detected.
 * Used for granular risk assessment and logging.
 */
enum ProxyType: string
{
    case NONE = 'none';
    case DATACENTER = 'datacenter';
    case RESIDENTIAL = 'residential';
    case MOBILE = 'mobile';
    case ISP_BACKED = 'isp_backed';
    case ROTATING_RESIDENTIAL = 'rotating_residential';
    case ETHICAL_RESIDENTIAL = 'ethical_residential';
    case CORPORATE_VPN = 'corporate_vpn';
    case TOR = 'tor';
    case UNKNOWN = 'unknown';

    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::NONE => 'Нет прокси',
            self::DATACENTER => 'Дата-центр',
            self::RESIDENTIAL => 'Резиденциальный прокси',
            self::MOBILE => 'Мобильный прокси',
            self::ISP_BACKED => 'ISP-backed прокси',
            self::ROTATING_RESIDENTIAL => 'Ротирующийся резиденциальный',
            self::ETHICAL_RESIDENTIAL => 'Этичный резиденциальный',
            self::CORPORATE_VPN => 'Корпоративный VPN',
            self::TOR => 'Tor сеть',
            self::UNKNOWN => 'Неизвестный тип',
        };
    }

    /**
     * Check if this proxy type is considered high-risk
     */
    public function isHighRisk(): bool
    {
        return in_array($this, [
            self::RESIDENTIAL,
            self::ROTATING_RESIDENTIAL,
            self::ETHICAL_RESIDENTIAL,
            self::TOR,
        ], true);
    }

    /**
     * Check if this proxy type is considered medium-risk
     */
    public function isMediumRisk(): bool
    {
        return in_array($this, [
            self::DATACENTER,
            self::MOBILE,
            self::ISP_BACKED,
        ], true);
    }

    /**
     * Check if this proxy type is whitelisted (allowed)
     */
    public function isWhitelisted(): bool
    {
        return $this === self::CORPORATE_VPN || $this === self::NONE;
    }

    /**
     * Get default risk level for this proxy type
     */
    public function getDefaultRiskLevel(): VpnRiskLevel
    {
        return match ($this) {
            self::NONE => VpnRiskLevel::LOW,
            self::CORPORATE_VPN => VpnRiskLevel::LOW,
            self::DATACENTER => VpnRiskLevel::MEDIUM,
            self::MOBILE => VpnRiskLevel::MEDIUM,
            self::ISP_BACKED => VpnRiskLevel::MEDIUM,
            self::RESIDENTIAL => VpnRiskLevel::HIGH,
            self::ROTATING_RESIDENTIAL => VpnRiskLevel::HIGH,
            self::ETHICAL_RESIDENTIAL => VpnRiskLevel::HIGH,
            self::TOR => VpnRiskLevel::CRITICAL,
            self::UNKNOWN => VpnRiskLevel::MEDIUM,
        };
    }
}
