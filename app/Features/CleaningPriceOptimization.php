<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CleaningPriceOptimization Feature
 *
 * Controls the rollout of cleaning-price-optimization functionality.
 */
final class CleaningPriceOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'cleaning';
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
