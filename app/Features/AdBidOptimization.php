<?php

declare(strict_types=1);

namespace App\Features;

/**
 * AdBidOptimization Feature
 *
 * Controls the rollout of ad-bid-optimization functionality.
 */
final class AdBidOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'advertising';
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
