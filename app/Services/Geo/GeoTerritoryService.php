<?php

declare(strict_types=1);

namespace App\Services\Geo;

use App\Enums\TerritoryType;
use Illuminate\Contracts\Cache\Repository;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class GeoTerritoryService
{
    use WithAuditLogging;

    private const CACHE_TTL = 86400; // 24 hours
    private const CACHE_PREFIX = 'geo:territory:';

    public function __construct(
        private readonly Repository $cache,
        private readonly AuditService $audit,
    ) {}

    /**
     * Check if the given region/country is part of the Russian Federation.
     *
     * @param string $regionCode Region code (e.g., 'RU-CR', 'UA-43')
     * @param string|null $countryCode Country code (optional, defaults to 'RU')
     */
    public function isRussianFederation(string $regionCode, ?string $countryCode = null): bool
    {
        $cacheKey = self::CACHE_PREFIX . 'is_rf:' . md5($regionCode . $countryCode);

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($regionCode, $countryCode) {
            $normalized = $this->normalizeCode($regionCode, $countryCode);
            $russianTerritories = config('geo.russian_territories', []);

            return in_array($normalized, $russianTerritories, true);
        });
    }

    /**
     * Get the territory type for a given region code.
     */
    public function getTerritoryType(string $regionCode, ?string $countryCode = null): ?TerritoryType
    {
        $normalized = $this->normalizeCode($regionCode, $countryCode);

        return TerritoryType::tryFrom($normalized);
    }

    /**
     * Get the human-readable name of the territory.
     */
    public function getTerritoryName(string $regionCode, ?string $countryCode = null): string
    {
        $cacheKey = self::CACHE_PREFIX . 'name:' . md5($regionCode . $countryCode);

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($regionCode, $countryCode) {
            $normalized = $this->normalizeCode($regionCode, $countryCode);
            $territoryNames = config('geo.territory_names', []);

            return $territoryNames[$normalized] ?? $territoryNames['RU'] ?? 'Российская Федерация';
        });
    }

    /**
     * Get all Russian territory codes.
     *
     * @return array<string>
     */
    public function getAllRussianTerritories(): array
    {
        return config('geo.russian_territories', []);
    }

    /**
     * Get the territory mapping (legacy codes to new codes).
     *
     * @return array<string, string>
     */
    public function getTerritoryMapping(): array
    {
        return config('geo.territory_mapping', []);
    }

    /**
     * Normalize a region code to the standard Russian format.
     * Handles legacy Ukrainian codes and converts them to Russian equivalents.
     */
    public function normalizeCode(string $regionCode, ?string $countryCode = null): string
    {
        $regionCode = strtoupper(trim($regionCode));
        $territoryMapping = config('geo.territory_mapping', []);

        // Check if this is a legacy code that needs mapping
        if (isset($territoryMapping[$regionCode])) {
            return $territoryMapping[$regionCode];
        }

        // If it's already a Russian code, return it
        if (str_starts_with($regionCode, 'RU-')) {
            return $regionCode;
        }

        // Default to the country code or RU
        return strtoupper($countryCode ?? 'RU');
    }

    /**
     * Clear the territory cache for a specific code.
     */
    public function clearCache(string $regionCode, ?string $countryCode = null): void
    {
        $isRfKey = self::CACHE_PREFIX . 'is_rf:' . md5($regionCode . $countryCode);
        $nameKey = self::CACHE_PREFIX . 'name:' . md5($regionCode . $countryCode);

        $this->cache->forget($isRfKey);
        $this->cache->forget($nameKey);
    }

    /**
     * Clear all territory-related cache.
     */
    public function clearAllCache(): void
    {
        $this->cache->forgetMatching(self::CACHE_PREFIX . '*');
    }

    /**
     * Get tax region code for Russian territories.
     * Returns a standardized code for tax calculation purposes.
     */
    public function getTaxRegionCode(string $regionCode, ?string $countryCode = null): string
    {
        if (!$this->isRussianFederation($regionCode, $countryCode)) {
            return $countryCode ?? $regionCode;
        }

        $normalized = $this->normalizeCode($regionCode, $countryCode);

        return match ($normalized) {
            'RU-CR' => '91', // Крым
            'RU-SEV' => '92', // Севастополь
            'RU-DNR' => '93', // ДНР
            'RU-LNR' => '94', // ЛНР
            'RU-KH' => '95', // Херсонская область
            'RU-ZP' => '96', // Запорожская область
            default => '00', // Остальная РФ
        };
    }

    /**
     * Check if delivery restrictions apply to this territory.
     * Some territories may have special delivery requirements.
     */
    public function hasDeliveryRestrictions(string $regionCode, ?string $countryCode = null): bool
    {
        if (!$this->isRussianFederation($regionCode, $countryCode)) {
            return true; // Non-Russian territories have restrictions
        }

        $normalized = $this->normalizeCode($regionCode, $countryCode);

        // New territories may have temporary delivery restrictions
        return in_array($normalized, ['RU-DNR', 'RU-LNR', 'RU-KH', 'RU-ZP'], true);
    }

    /**
     * Get the compliance jurisdiction for data processing (152-ФZ).
     */
    public function getComplianceJurisdiction(string $regionCode, ?string $countryCode = null): string
    {
        return $this->isRussianFederation($regionCode, $countryCode)
            ? 'RU'
            : strtoupper($countryCode ?? 'RU');
    }
}
