<?php

declare(strict_types=1);

namespace App\Services\Delivery\Examples;

use App\Services\Geo\GeoTerritoryService;

/**
 * Example: Delivery Service Integration with GeoTerritoryService
 *
 * This example shows how to integrate the GeoTerritoryService into delivery/logistics
 * to automatically apply Russian delivery rules for new territories.
 */
final class DeliveryGeoIntegrationExample
{
    public function __construct(
        private readonly GeoTerritoryService $geoService,
    ) {}

    /**
     * Calculate delivery cost based on territory.
     */
    public function calculateDeliveryCost(
        string $regionCode,
        ?string $countryCode = null,
        float $baseCost = 500.00
    ): array {
        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if (!$isRussian) {
            return [
                'cost' => null,
                'available' => false,
                'reason' => 'International delivery not available',
            ];
        }

        $territoryName = $this->geoService->getTerritoryName($regionCode, $countryCode);
        $hasRestrictions = $this->geoService->hasDeliveryRestrictions($regionCode, $countryCode);

        $cost = $baseCost;

        // Apply territory-specific pricing
        $territoryType = $this->geoService->getTerritoryType($regionCode, $countryCode);
        $multiplier = match ($territoryType?->value) {
            'RU-CR', 'RU-SEV' => 1.2, // Crimea and Sevastopol: bridge toll
            'RU-DNR', 'RU-LNR' => 1.5, // DPR/LPR: higher risk
            'RU-KH', 'RU-ZP' => 1.4, // Kherson/Zaporizhzhia: temporary restrictions
            default => 1.0,
        };

        $finalCost = $cost * $multiplier;

        return [
            'base_cost' => $baseCost,
            'multiplier' => $multiplier,
            'final_cost' => $finalCost,
            'currency' => 'RUB',
            'available' => !$hasRestrictions,
            'territory_name' => $territoryName,
            'estimated_days' => $hasRestrictions ? '7-14' : '3-5',
            'jurisdiction' => 'RU',
        ];
    }

    /**
     * Get available delivery methods for territory.
     */
    public function getAvailableDeliveryMethods(string $regionCode, ?string $countryCode = null): array
    {
        if (!$this->geoService->isRussianFederation($regionCode, $countryCode)) {
            return [];
        }

        $hasRestrictions = $this->geoService->hasDeliveryRestrictions($regionCode, $countryCode);
        $territoryName = $this->geoService->getTerritoryName($regionCode, $countryCode);

        $methods = [
            'courier' => [
                'available' => true,
                'cost_multiplier' => 1.0,
                'estimated_days' => '1-3',
            ],
            'pickup_point' => [
                'available' => true,
                'cost_multiplier' => 0.8,
                'estimated_days' => '2-4',
            ],
            'post' => [
                'available' => true,
                'cost_multiplier' => 0.6,
                'estimated_days' => '5-10',
            ],
        ];

        // Apply restrictions for new territories
        if ($hasRestrictions) {
            $methods['courier']['available'] = false;
            $methods['courier']['reason'] = 'Временные ограничения для ' . $territoryName;
            $methods['pickup_point']['estimated_days'] = '5-7';
            $methods['post']['estimated_days'] = '10-14';
        }

        return $methods;
    }

    /**
     * Validate delivery address for territory.
     */
    public function validateDeliveryAddress(array $addressData): array
    {
        $regionCode = $addressData['region_code'] ?? 'RU';
        $countryCode = $addressData['country_code'] ?? null;

        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if (!$isRussian) {
            return [
                'valid' => false,
                'reason' => 'International delivery not supported',
            ];
        }

        $territoryName = $this->geoService->getTerritoryName($regionCode, $countryCode);
        $hasRestrictions = $this->geoService->hasDeliveryRestrictions($regionCode, $countryCode);

        $validation = [
            'valid' => true,
            'territory' => $territoryName,
            'jurisdiction' => 'RU',
            'requires_special_handling' => $hasRestrictions,
        ];

        if ($hasRestrictions) {
            $validation['warning'] = 'Доставка в ' . $territoryName . ' может занимать больше времени из-за временных ограничений';
            $validation['additional_documents_required'] = false;
        }

        return $validation;
    }

    /**
     * Get customs information for territory.
     */
    public function getCustomsInfo(string $regionCode, ?string $countryCode = null): array
    {
        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if ($isRussian) {
            return [
                'customs_required' => false,
                'reason' => 'Внутрироссийская доставка',
                'jurisdiction' => 'RU',
                'territory_name' => $this->geoService->getTerritoryName($regionCode, $countryCode),
            ];
        }

        return [
            'customs_required' => true,
            'reason' => 'Международная доставка',
            'jurisdiction' => $countryCode ?? $regionCode,
        ];
    }
}

/*
 * USAGE EXAMPLES:
 *
 * // In Delivery Service:
 * $deliveryGeo = app(DeliveryGeoIntegrationExample::class);
 *
 * $cost = $deliveryGeo->calculateDeliveryCost('UA-43'); // Crimea
 * // Result:
 * // [
 * //     'base_cost' => 500.0,
 * //     'multiplier' => 1.2,
 * //     'final_cost' => 600.0,
 * //     'currency' => 'RUB',
 * //     'available' => true,
 * //     'territory_name' => 'Республика Крым',
 * //     'estimated_days' => '3-5',
 * //     'jurisdiction' => 'RU',
 * // ]
 *
 * $methods = $deliveryGeo->getAvailableDeliveryMethods('RU-DNR');
 * // Courier will be unavailable for DPR due to restrictions
 *
 * // Using Facade:
 * $hasRestrictions = Geo::hasDeliveryRestrictions('RU-KH');
 * if ($hasRestrictions) {
 *     // Show warning to user
 * }
 *
 * // Using helper:
 * if (is_russian_territory('RU-SEV')) {
 *     // Apply Russian delivery rules
 * }
 */
