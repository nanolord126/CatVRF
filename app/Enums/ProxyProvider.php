<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Proxy Provider Enum
 *
 * Known residential proxy providers for detection and blocking.
 * Updated regularly based on threat intelligence.
 */
enum ProxyProvider: string
{
    case UNKNOWN = 'unknown';
    case OXYLABS = 'oxylabs';
    case BRIGHT_DATA = 'bright_data';
    case IPROYAL = 'iproyal';
    case DECODO = 'decodo';
    case SMARTPROXY = 'smartproxy';
    case LUMINATI = 'luminati';
    case STORM_PROXIES = 'storm_proxies';
    case HYDRA_PROXIES = 'hydra_proxies';
    case SHIFTER = 'shifter';
    case NETNUT = 'netnut';
    case ZENROW = 'zenrow';
    case SOAX = 'soax';
    case INFATICA = 'infatica';
    case IPIUM = 'ipium';
    case PROXYSELL = 'proxysell';
    case ASTROPROXY = 'astroproxy';
    case BLAZINGSEO = 'blazingseo';
    case GEOsurf = 'geosurf';
    case APIFY = 'apify';
    case SCRAPERAPI = 'scraperapi';
    case ZYTE = 'zyte';

    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::UNKNOWN => 'Неизвестный провайдер',
            self::OXYLABS => 'Oxylabs',
            self::BRIGHT_DATA => 'Bright Data (Luminati)',
            self::IPROYAL => 'IPRoyal',
            self::DECODO => 'Decodo',
            self::SMARTPROXY => 'Smartproxy',
            self::LUMINATI => 'Luminati',
            self::STORM_PROXIES => 'Storm Proxies',
            self::HYDRA_PROXIES => 'Hydra Proxies',
            self::SHIFTER => 'Shifter',
            self::NETNUT => 'NetNut',
            self::ZENROW => 'ZenRows',
            self::SOAX => 'SOAX',
            self::INFATICA => 'Infatica',
            self::IPIUM => 'IPIUM',
            self::PROXYSELL => 'ProxySell',
            self::ASTROPROXY => 'AstroProxy',
            self::BLAZINGSEO => 'Blazing SEO',
            self::GEOsurf => 'GeoSurf',
            self::APIFY => 'Apify',
            self::SCRAPERAPI => 'ScraperAPI',
            self::ZYTE => 'Zyte (Scrapinghub)',
        };
    }

    /**
     * Check if this provider is considered high-risk (known for abuse)
     */
    public function isHighRisk(): bool
    {
        return in_array($this, [
            self::BRIGHT_DATA,
            self::LUMINATI,
            self::HYDRA_PROXIES,
            self::SHIFTER,
            self::SCRAPERAPI,
        ], true);
    }

    /**
     * Get provider category for risk assessment
     */
    public function getCategory(): string
    {
        return match ($this) {
            self::UNKNOWN => 'unknown',
            self::OXYLABS, self::BRIGHT_DATA, self::IPROYAL, self::SMARTPROXY => 'enterprise',
            self::LUMINATI, self::STORM_PROXIES, self::HYDRA_PROXIES, self::SHIFTER => 'high_risk',
            self::NETNUT, self::ZENROW, self::SOAX, self::INFATICA => 'mid_tier',
            self::IPIUM, self::PROXYSELL, self::ASTROPROXY => 'budget',
            self::BLAZINGSEO, self::GEOsurf, self::APIFY, self::SCRAPERAPI, self::ZYTE => 'scraping',
        };
    }

    /**
     * Get confidence score for this provider (0-100)
     * Higher score = more likely to be abusive
     */
    public function getConfidenceScore(): int
    {
        return match ($this) {
            self::BRIGHT_DATA, self::LUMINATI => 95,
            self::HYDRA_PROXIES, self::SHIFTER => 90,
            self::SCRAPERAPI, self::ZYTE => 85,
            self::OXYLABS, self::SMARTPROXY => 75,
            self::IPROYAL, self::DECODO => 70,
            self::NETNUT, self::ZENROW, self::SOAX => 65,
            self::INFATICA, self::IPIUM => 60,
            self::PROXYSELL, self::ASTROPROXY => 55,
            self::BLAZINGSEO, self::GEOsurf => 50,
            self::APIFY => 45,
            self::UNKNOWN => 0,
        };
    }

    /**
     * Check if provider is ethical (claims to be)
     */
    public function isEthical(): bool
    {
        return in_array($this, [
            self::OXYLABS,
            self::IPROYAL,
            self::NETNUT,
            self::ZENROW,
        ], true);
    }
}
