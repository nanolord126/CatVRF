<?php

declare(strict_types=1);

namespace App\Features;

/**
 * GeoGeofencing Feature
 *
 * Controls the rollout of geo-geofencing functionality.
 */
final class GeoGeofencing extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'geo';
    }

    protected function getBetaTenants(): array
    {
        return [];
    }

    protected function canActivate(): bool
    {
        return true;
    }
}
