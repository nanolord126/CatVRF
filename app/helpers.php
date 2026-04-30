<?php

declare(strict_types=1);

use App\Facades\Geo;

if (!function_exists('geo')) {
    /**
     * Get the GeoTerritoryService instance.
     *
     * @return \App\Services\Geo\GeoTerritoryService
     */
    function geo(): \App\Services\Geo\GeoTerritoryService
    {
        return app('geo');
    }
}

if (!function_exists('is_russian_territory')) {
    /**
     * Check if a region code belongs to the Russian Federation.
     *
     * @param string $regionCode Region code (e.g., 'RU-CR', 'UA-43')
     * @param string|null $countryCode Country code (optional)
     */
    function is_russian_territory(string $regionCode, ?string $countryCode = null): bool
    {
        return Geo::isRussianFederation($regionCode, $countryCode);
    }
}

if (!function_exists('get_territory_name')) {
    /**
     * Get the human-readable name of a territory.
     *
     * @param string $regionCode Region code (e.g., 'RU-CR', 'UA-43')
     * @param string|null $countryCode Country code (optional)
     */
    function get_territory_name(string $regionCode, ?string $countryCode = null): string
    {
        return Geo::getTerritoryName($regionCode, $countryCode);
    }
}
