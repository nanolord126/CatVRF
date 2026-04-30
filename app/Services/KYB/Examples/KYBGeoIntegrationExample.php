<?php

declare(strict_types=1);

namespace App\Services\KYB\Examples;

use App\Services\Geo\GeoTerritoryService;
use App\Enums\TerritoryType;

/**
 * Example: KYB (Know Your Business) Integration with GeoTerritoryService
 *
 * This example shows how to integrate the GeoTerritoryService into KYB verification
 * to automatically apply Russian business rules for new territories.
 */
final class KYBGeoIntegrationExample
{
    public function __construct(
        private readonly GeoTerritoryService $geoService,
    ) {}

    /**
     * Process business registration with automatic territory detection.
     */
    public function processBusinessRegistration(array $registrationData): array
    {
        $regionCode = $registrationData['region_code'] ?? 'RU';
        $countryCode = $registrationData['country_code'] ?? null;

        // Check if the business is in Russian territory
        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if ($isRussian) {
            // Apply Russian business registration rules
            $registrationData['jurisdiction'] = 'RU';
            $registrationData['tax_region_code'] = $this->geoService->getTaxRegionCode($regionCode, $countryCode);
            $registrationData['currency'] = 'RUB';
            $registrationData['compliance_framework'] = '152-FZ';
            $registrationData['territory_name'] = $this->geoService->getTerritoryName($regionCode, $countryCode);

            // Set Russian-specific OKVED codes if applicable
            $registrationData['okved_required'] = true;
        }

        return $registrationData;
    }

    /**
     * Validate business documents based on territory.
     */
    public function validateBusinessDocuments(string $regionCode, ?string $countryCode = null): array
    {
        $territoryType = $this->geoService->getTerritoryType($regionCode, $countryCode);

        $requirements = [
            'inn_required' => true,
            'kpp_required' => true,
            'ogrn_required' => true,
        ];

        // New territories may have temporary simplified requirements
        if (in_array($territoryType, [
            TerritoryType::DPR,
            TerritoryType::LPR,
            TerritoryType::KHERSON,
            TerritoryType::ZAPORIZHZHIA,
        ], true)) {
            $requirements['grace_period_days'] = 180;
            $requirements['simplified_registration'] = true;
        }

        return $requirements;
    }

    /**
     * Get applicable tax rules for the business territory.
     */
    public function getTaxRules(string $regionCode, ?string $countryCode = null): array
    {
        if (!$this->geoService->isRussianFederation($regionCode, $countryCode)) {
            return [
                'vat_rate' => null,
                'profit_tax_rate' => null,
                'currency' => null,
            ];
        }

        $taxRegionCode = $this->geoService->getTaxRegionCode($regionCode, $countryCode);

        return [
            'vat_rate' => '20%', // Standard Russian VAT
            'profit_tax_rate' => '20%',
            'currency' => 'RUB',
            'tax_region_code' => $taxRegionCode,
            'jurisdiction' => 'RU',
            'territory_name' => $this->geoService->getTerritoryName($regionCode, $countryCode),
        ];
    }

    /**
     * Check if additional verification is required for the territory.
     */
    public function requiresAdditionalVerification(string $regionCode, ?string $countryCode = null): bool
    {
        // New territories may require additional AML checks
        $territoryType = $this->geoService->getTerritoryType($regionCode, $countryCode);

        return in_array($territoryType, [
            TerritoryType::DPR,
            TerritoryType::LPR,
            TerritoryType::KHERSON,
            TerritoryType::ZAPORIZHZHIA,
        ], true);
    }
}

/*
 * USAGE EXAMPLES:
 *
 * // In KYB Service:
 * $kybGeo = app(KYBGeoIntegrationExample::class);
 *
 * $registrationData = [
 *     'region_code' => 'UA-43', // Legacy code for Crimea
 *     'company_name' => 'ООО Пример',
 * ];
 *
 * $processed = $kybGeo->processBusinessRegistration($registrationData);
 * // Result:
 * // [
 * //     'region_code' => 'UA-43',
 * //     'company_name' => 'ООО Пример',
 * //     'jurisdiction' => 'RU',
 * //     'tax_region_code' => '91',
 * //     'currency' => 'RUB',
 * //     'compliance_framework' => '152-FZ',
 * //     'territory_name' => 'Республика Крым',
 * //     'okved_required' => true,
 * // ]
 *
 * // Using Facade:
 * $isRussian = Geo::isRussianFederation('RU-DNR'); // true
 * $territoryName = Geo::getTerritoryName('UA-14'); // "Донецкая Народная Республика"
 *
 * // Using helper:
 * if (is_russian_territory('RU-CR')) {
 *     // Apply Russian rules
 * }
 */
