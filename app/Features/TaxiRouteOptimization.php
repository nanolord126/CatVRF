<?php

declare(strict_types=1);

namespace App\Features;

/**
 * TaxiRouteOptimization Feature
 *
 * Controls the rollout of taxi-route-optimization functionality.
 */
final class TaxiRouteOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'taxi';
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
