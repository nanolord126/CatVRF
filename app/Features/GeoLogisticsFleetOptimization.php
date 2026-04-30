<?php

declare(strict_types=1);

namespace App\Features;

/**
 * GeoLogisticsFleetOptimization Feature
 *
 * Controls the rollout of geo-logistics-fleet-optimization functionality.
 */
final class GeoLogisticsFleetOptimization extends BaseVerticalFeature
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
