<?php

declare(strict_types=1);

namespace App\Features;

/**
 * GeoRouteOptimization Feature
 *
 * Controls the rollout of geo-route-optimization functionality.
 */
final class GeoRouteOptimization extends BaseVerticalFeature
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
