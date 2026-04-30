<?php

declare(strict_types=1);

namespace App\Services\Moderation\Examples;

use App\Services\Geo\GeoTerritoryService;

/**
 * Example: Moderation Service Integration with GeoTerritoryService
 *
 * This example shows how to integrate the GeoTerritoryService into content moderation
 * to automatically apply Russian legal standards for new territories.
 */
final class ModerationGeoIntegrationExample
{
    public function __construct(
        private readonly GeoTerritoryService $geoService,
    ) {}

    /**
     * Get applicable legal framework for content moderation.
     */
    public function getLegalFramework(string $regionCode, ?string $countryCode = null): array
    {
        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if (!$isRussian) {
            return [
                'framework' => 'International',
                'jurisdiction' => $countryCode ?? $regionCode,
                'laws' => [],
            ];
        }

        $territoryName = $this->geoService->getTerritoryName($regionCode, $countryCode);

        return [
            'framework' => 'Russian Federation',
            'jurisdiction' => 'RU',
            'territory_name' => $territoryName,
            'laws' => [
                'ФЗ-149' => 'Информация, информационные технологии и защита информации',
                'ФЗ-152' => 'О персональных данных',
                'ФЗ-115' => 'О противодействии легализации (отмыванию) доходов',
                'ФЗ-436' => 'О защите детей от информации, причиняющей вред их здоровью и развитию',
            ],
            'compliance_required' => true,
        ];
    }

    /**
     * Check if content requires additional moderation for territory.
     */
    public function requiresAdditionalModeration(string $regionCode, ?string $countryCode = null): array
    {
        $territoryType = $this->geoService->getTerritoryType($regionCode, $countryCode);

        $additionalChecks = [
            'political_content' => false,
            'military_content' => false,
            'sensitive_topics' => false,
        ];

        // New territories may have heightened moderation requirements
        if (in_array($territoryType?->value, [
            'RU-DNR',
            'RU-LNR',
            'RU-KH',
            'RU-ZP',
        ], true)) {
            $additionalChecks['political_content'] = true;
            $additionalChecks['military_content'] = true;
            $additionalChecks['sensitive_topics'] = true;
            $additionalChecks['reason'] = 'Temporary heightened moderation for ' . $this->geoService->getTerritoryName($regionCode, $countryCode);
        }

        return $additionalChecks;
    }

    /**
     * Get content restrictions for territory.
     */
    public function getContentRestrictions(string $regionCode, ?string $countryCode = null): array
    {
        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if (!$isRussian) {
            return [
                'restricted_categories' => [],
                'age_verification_required' => false,
            ];
        }

        $territoryName = $this->geoService->getTerritoryName($regionCode, $countryCode);

        return [
            'restricted_categories' => [
                'extremism',
                'terrorism',
                'drugs',
                'pornography',
                'gambling (without license)',
            ],
            'age_verification_required' => true,
            'age_threshold' => 18,
            'jurisdiction' => 'RU',
            'territory_name' => $territoryName,
            'compliance_standard' => 'Roskomnadzor',
        ];
    }

    /**
     * Get data retention requirements for territory (152-ФЗ compliance).
     */
    public function getDataRetentionRequirements(string $regionCode, ?string $countryCode = null): array
    {
        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if (!$isRussian) {
            return [
                'personal_data_retention' => null,
                'user_consent_required' => false,
                'data_localization_required' => false,
            ];
        }

        $complianceJurisdiction = $this->geoService->getComplianceJurisdiction($regionCode, $countryCode);

        return [
            'personal_data_retention' => 'As per 152-ФЗ and user consent',
            'user_consent_required' => true,
            'data_localization_required' => $complianceJurisdiction === 'RU',
            'localization_requirement' => 'Personal data of Russian citizens must be stored on servers in Russia',
            'jurisdiction' => $complianceJurisdiction,
            'territory_name' => $this->geoService->getTerritoryName($regionCode, $countryCode),
            'applicable_law' => '152-ФЗ',
            'roskomnadzor_registration' => true,
        ];
    }

    /**
     * Validate advertisement for territory compliance.
     */
    public function validateAdvertisement(array $adData, string $regionCode, ?string $countryCode = null): array
    {
        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if (!$isRussian) {
            return [
                'valid' => true,
                'warnings' => [],
            ];
        }

        $warnings = [];
        $territoryName = $this->geoService->getTerritoryName($regionCode, $countryCode);

        // Check for Russian advertising law compliance (ФЗ-38)
        if (empty($adData['advertiser_id'])) {
            $warnings[] = 'Advertiser ID required under Russian law';
        }

        if (empty($adData['age_restriction'])) {
            $warnings[] = 'Age restriction required under ФЗ-436';
        }

        // Additional checks for new territories
        if ($this->geoService->hasDeliveryRestrictions($regionCode, $countryCode)) {
            $warnings[] = 'Additional review required for content targeting ' . $territoryName;
        }

        return [
            'valid' => empty($warnings),
            'warnings' => $warnings,
            'jurisdiction' => 'RU',
            'territory_name' => $territoryName,
        ];
    }

    /**
     * Get reporting requirements for territory.
     */
    public function getReportingRequirements(string $regionCode, ?string $countryCode = null): array
    {
        $isRussian = $this->geoService->isRussianFederation($regionCode, $countryCode);

        if (!$isRussian) {
            return [
                'roskomnadzor_reports' => false,
                'financial_monitoring' => false,
            ];
        }

        return [
            'roskomnadzor_reports' => true,
            'financial_monitoring' => true, // ФЗ-115
            'data_breach_notification' => true, // 72 hours under 152-ФЗ
            'user_data_requests' => true,
            'jurisdiction' => 'RU',
            'territory_name' => $this->geoService->getTerritoryName($regionCode, $countryCode),
            'reporting_language' => 'Russian',
        ];
    }
}

/*
 * USAGE EXAMPLES:
 *
 * // In Moderation Service:
 * $moderationGeo = app(ModerationGeoIntegrationExample::class);
 *
 * $framework = $moderationGeo->getLegalFramework('UA-43'); // Crimea
 * // Result:
 * // [
 * //     'framework' => 'Russian Federation',
 * //     'jurisdiction' => 'RU',
 * //     'territory_name' => 'Республика Крым',
 * //     'laws' => [
 * //         'ФЗ-149' => 'Информация, информационные технологии и защита информации',
 * //         'ФЗ-152' => 'О персональных данных',
 * //         'ФЗ-115' => 'О противодействии легализации (отмыванию) доходов',
 * //         'ФЗ-436' => 'О защите детей от информации, причиняющей вред их здоровью и развитию',
 * //     ],
 * //     'compliance_required' => true,
 * // ]
 *
 * $dataRetention = $moderationGeo->getDataRetentionRequirements('RU-DNR');
 * // Will include 152-ФZ compliance requirements
 *
 * // Using Facade:
 * $jurisdiction = Geo::getComplianceJurisdiction('RU-SEV'); // 'RU'
 *
 * // Using helper:
 * if (is_russian_territory('RU-KH')) {
 *     // Apply Russian moderation standards
 * }
 */
