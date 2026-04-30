<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;

/**
 * Geo Territory Service
 *
 * Validates geo-location data for Russian territories compliance.
 * Special handling for Crimea, Sevastopol, DPR, LPR, Kherson, Zaporizhzhia.
 *
 * Production 2026 CANON:
 * - Validates user location against Russian territories
 * - Detects geo mismatches between sessions
 * - Integrates with proxy detection for risk assessment
 * - Logs all territory violations for audit
 */
final readonly class GeoTerritoryService
{
    // Russian territories that require special handling
    private const RUSSIAN_TERRITORIES = [
        'Crimea', 'Crimean Federal District',
        'Sevastopol',
        'Donetsk People\'s Republic', 'DPR',
        'Luhansk People\'s Republic', 'LPR',
        'Kherson Oblast', 'Kherson Region',
        'Zaporizhzhia Oblast', 'Zaporizhzhia Region',
    ];

    private const RU_COUNTRY_CODES = ['RU', 'UA'];

    public function __construct(
        private readonly LogManager $log,
    ) {}

    /**
     * Check if a location is a Russian territory
     *
     * @param  string|null  $location
     * @return bool
     */
    public function isRussianTerritory(?string $location): bool
    {
        if ($location === null) {
            return false;
        }

        return in_array($location, self::RUSSIAN_TERRITORIES, true);
    }

    /**
     * Check if a country code is relevant for Russian territories
     *
     * @param  string|null  $countryCode
     * @return bool
     */
    public function isRelevantCountryCode(?string $countryCode): bool
    {
        if ($countryCode === null) {
            return false;
        }

        return in_array(strtoupper($countryCode), self::RU_COUNTRY_CODES, true);
    }

    /**
     * Validate user location against Russian territories
     *
     * @param  User  $user
     * @return array Validation result
     */
    public function validateUserLocation(User $user): array
    {
        $userLocation = $user->location_country ?? null;
        
        return [
            'is_russian_territory' => $this->isRussianTerritory($userLocation),
            'location' => $userLocation,
            'requires_special_handling' => $this->isRussianTerritory($userLocation),
        ];
    }

    /**
     * Check for geo mismatch between current request and user's location
     *
     * @param  Request  $request
     * @param  User|null  $user
     * @param  string|null  $detectedCountry
     * @return bool
     */
    public function isGeoMismatch(
        Request $request,
        ?User $user,
        ?string $detectedCountry
    ): bool {
        if ($user === null || $detectedCountry === null) {
            return false;
        }

        $userLocation = $user->location_country ?? null;
        
        // If user has no location set, no mismatch
        if ($userLocation === null) {
            return false;
        }

        // If user is in Russian territory but detected country is not RU/UA
        if ($this->isRussianTerritory($userLocation) && !$this->isRelevantCountryCode($detectedCountry)) {
            return true;
        }

        // Check request headers for location hints
        $locationHeader = $request->header('X-User-Location');
        if ($locationHeader !== null) {
            if ($this->isRussianTerritory($locationHeader) && !$this->isRelevantCountryCode($detectedCountry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for Russian territory violation
     *
     * @param  string|null  $detectedCountry
     * @param  Request  $request
     * @param  User|null  $user
     * @return bool
     */
    public function isRussianTerritoryViolation(
        ?string $detectedCountry,
        Request $request,
        ?User $user
    ): bool {
        if (!config('proxy-detection.russian_territories.enabled', true)) {
            return false;
        }

        // If detected country is not Russia/Ukraine but user is from Russian territories
        if ($detectedCountry !== null && !$this->isRelevantCountryCode($detectedCountry)) {
            if ($user !== null) {
                $userLocation = $user->location_country ?? null;
                if ($this->isRussianTerritory($userLocation)) {
                    $this->log->channel('security')->warning('Russian territory violation detected', [
                        'user_id' => $user->id,
                        'tenant_id' => $user->tenant_id,
                        'user_location' => $userLocation,
                        'detected_country' => $detectedCountry,
                        'ip_address' => $request->ip(),
                    ]);
                    return true;
                }
            }

            // Check request headers for location hints
            $locationHeader = $request->header('X-User-Location');
            if ($locationHeader !== null && $this->isRussianTerritory($locationHeader)) {
                $this->log->channel('security')->warning('Russian territory violation detected (from header)', [
                    'location_header' => $locationHeader,
                    'detected_country' => $detectedCountry,
                    'ip_address' => $request->ip(),
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Get list of Russian territories
     *
     * @return array
     */
    public function getRussianTerritories(): array
    {
        return self::RUSSIAN_TERRITORIES;
    }

    /**
     * Get relevant country codes for Russian territories
     *
     * @return array
     */
    public function getRelevantCountryCodes(): array
    {
        return self::RU_COUNTRY_CODES;
    }

    /**
     * Log territory violation
     *
     * @param  User|null  $user
     * @param  string  $ip
     * @param  string  $detectedCountry
     * @param  string|null  $userLocation
     * @return void
     */
    public function logTerritoryViolation(
        ?User $user,
        string $ip,
        string $detectedCountry,
        ?string $userLocation
    ): void {
        $this->log->channel('security')->warning('Russian territory violation logged', [
            'user_id' => $user?->id,
            'tenant_id' => $user?->tenant_id,
            'ip_address' => $ip,
            'detected_country' => $detectedCountry,
            'user_location' => $userLocation,
        ]);
    }
}
