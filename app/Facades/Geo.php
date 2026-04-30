<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isRussianFederation(string $regionCode, ?string $countryCode = null)
 * @method static \App\Enums\TerritoryType|null getTerritoryType(string $regionCode, ?string $countryCode = null)
 * @method static string getTerritoryName(string $regionCode, ?string $countryCode = null)
 * @method static array getAllRussianTerritories()
 * @method static array getTerritoryMapping()
 * @method static string normalizeCode(string $regionCode, ?string $countryCode = null)
 * @method static void clearCache(string $regionCode, ?string $countryCode = null)
 * @method static void clearAllCache()
 * @method static string getTaxRegionCode(string $regionCode, ?string $countryCode = null)
 * @method static bool hasDeliveryRestrictions(string $regionCode, ?string $countryCode = null)
 * @method static string getComplianceJurisdiction(string $regionCode, ?string $countryCode = null)
 *
 * @see \App\Services\Geo\GeoTerritoryService
 */
final class Geo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Geo\GeoTerritoryService::class;
    }
}
