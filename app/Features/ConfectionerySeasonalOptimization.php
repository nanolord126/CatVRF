<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ConfectionerySeasonalOptimization Feature
 *
 * Controls the rollout of confectionery-seasonal-optimization functionality.
 */
final class ConfectionerySeasonalOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'confectionery';
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
