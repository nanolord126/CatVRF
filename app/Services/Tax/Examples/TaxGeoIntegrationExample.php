<?php

declare(strict_types=1);

namespace App\Services\Tax\Examples;

use App\Services\Geo\GeoTerritoryService;

/**
 * Example: Tax Service Integration with GeoTerritoryService
 *
 * This example shows how to integrate the GeoTerritoryService into tax calculation
 * to automatically apply Russian tax rules for new territories.
 */
final class TaxGeoIntegrationExample
{
    public function __construct(
        private readonly GeoTerritoryService $geoService,
    ) {}

    /**
     * Calculate VAT based on territory.
     */
    public function calculateVAT(string $regionCode, ?string $countryCode = null, float $amount): array
    {
        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if (!$isRussian) {
            return [
                'vat_rate' => null,
                'vat_amount' => null,
                'total' => $amount,
                'currency' => null,
                'jurisdiction' => $countryCode ?? $regionCode,
            ];
        }

        // Russian VAT is 20% standard
        $vatRate = 0.20;
        $vatAmount = $amount * $vatRate;
        $total = $amount + $vatAmount;

        return [
            'vat_rate' => '20%',
            'vat_amount' => $vatAmount,
            'total' => $total,
            'currency' => 'RUB',
            'jurisdiction' => 'RU',
            'territory_name' => $this->geoService->getTerritoryName($regionCode, $countryCode),
            'tax_region_code' => $this->geoService->getTaxRegionCode($regionCode, $countryCode),
        ];
    }

    /**
     * Get tax rates for territory.
     */
    public function getTaxRates(string $regionCode, ?string $countryCode = null): array
    {
        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if (!$isRussian) {
            return [
                'vat' => null,
                'profit_tax' => null,
                'income_tax' => null,
                'currency' => null,
                'jurisdiction' => $countryCode ?? $regionCode,
            ];
        }

        return [
            'vat' => '20%',
            'profit_tax' => '20%',
            'income_tax_individual' => '13%',
            'income_tax_progressive' => '15% (above 5M RUB)',
            'social_contributions' => '30%',
            'currency' => 'RUB',
            'jurisdiction' => 'RU',
            'territory_name' => $this->geoService->getTerritoryName($regionCode, $countryCode),
            'tax_region_code' => $this->geoService->getTaxRegionCode($regionCode, $countryCode),
            'special_tax_regime_available' => $this->hasSpecialTaxRegime($regionCode, $countryCode),
        ];
    }

    /**
     * Check if special tax regime is available (e.g., for new territories).
     */
    private function hasSpecialTaxRegime(string $regionCode, ?string $countryCode = null): bool
    {
        $territoryType = $this->geoService->getTerritoryType($regionCode, $countryCode);

        // New territories may have special tax incentives
        return in_array($territoryType?->value, [
            'RU-DNR',
            'RU-LNR',
            'RU-KH',
            'RU-ZP',
        ], true);
    }

    /**
     * Get invoice requirements for territory.
     */
    public function getInvoiceRequirements(string $regionCode, ?string $countryCode = null): array
    {
        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if (!$isRussian) {
            return [
                'requires_vat_invoice' => false,
                'requires_kkt' => false,
                'currency' => null,
            ];
        }

        $territoryName = $this->geoService->getTerritoryName($regionCode, $countryCode);

        return [
            'requires_vat_invoice' => true,
            'requires_kkt' => true, // Kassa Kontrollovoi Tekhniki (cash register)
            'currency' => 'RUB',
            'jurisdiction' => 'RU',
            'territory_name' => $territoryName,
            'invoice_format' => 'RF-EDO',
            'electronic_document_flow' => true,
        ];
    }

    /**
     * Get currency regulations for territory.
     */
    public function getCurrencyRegulations(string $regionCode, ?string $countryCode = null): array
    {
        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if (!$isRussian) {
            return [
                'base_currency' => null,
                'forex_controls' => null,
                'reporting_currency' => null,
            ];
        }

        return [
            'base_currency' => 'RUB',
            'forex_controls' => 'Central Bank of Russia',
            'reporting_currency' => 'RUB',
            'jurisdiction' => 'RU',
            'territory_name' => $this->geoService->getTerritoryName($regionCode, $countryCode),
            'requires_currency_declaration' => false,
        ];
    }

    /**
     * Validate tax ID for territory.
     */
    public function validateTaxId(string $taxId, string $regionCode, ?string $countryCode = null): array
    {
        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if (!$isRussian) {
            return [
                'valid' => false,
                'reason' => 'Non-Russian jurisdiction',
            ];
        }

        // Russian INN validation (10 or 12 digits)
        $isValidINN = preg_match('/^\d{10}$|^\d{12}$/', $taxId) === 1;

        return [
            'valid' => $isValidINN,
            'tax_id_type' => 'INN',
            'jurisdiction' => 'RU',
            'territory_name' => $this->geoService->getTerritoryName($regionCode, $countryCode),
        ];
    }
}

/*
 * USAGE EXAMPLES:
 *
 * // In Tax Service:
 * $taxGeo = app(TaxGeoIntegrationExample::class);
 *
 * $vat = $taxGeo->calculateVAT('UA-43', null, 1000.00); // Crimea
 * // Result:
 * // [
 * //     'vat_rate' => '20%',
 * //     'vat_amount' => 200.0,
 * //     'total' => 1200.0,
 * //     'currency' => 'RUB',
 * //     'jurisdiction' => 'RU',
 * //     'territory_name' => 'Республика Крым',
 * //     'tax_region_code' => '91',
 * // ]
 *
 * $rates = $taxGeo->getTaxRates('RU-DNR');
 * // Will include special tax regime information
 *
 * // Using Facade:
 * $taxRegionCode = Geo::getTaxRegionCode('RU-SEV'); // '92'
 *
 * // Using helper:
 * if (is_russian_territory('RU-KH')) {
 *     // Apply Russian tax rules
 * }
 */
