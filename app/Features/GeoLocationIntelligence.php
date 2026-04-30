<?php

declare(strict_types=1);

namespace App\Features;

/**
 * GeoLocationIntelligence Feature
 *
 * Controls the rollout of geo-location-intelligence functionality.
 */
final class GeoLocationIntelligence extends BaseVerticalFeature
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
