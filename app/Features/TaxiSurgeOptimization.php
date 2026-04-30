<?php

declare(strict_types=1);

namespace App\Features;

/**
 * TaxiSurgeOptimization Feature
 *
 * Controls the rollout of taxi-surge-optimization functionality.
 */
final class TaxiSurgeOptimization extends BaseVerticalFeature
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
