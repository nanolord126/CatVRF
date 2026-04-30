<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FarmDirectSupplyOptimization Feature
 *
 * Controls the rollout of farm-direct-supply-optimization functionality.
 */
final class FarmDirectSupplyOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'farmdirect';
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
