<?php

declare(strict_types=1);

namespace App\Features;

/**
 * MarketplaceDynamicPricing Feature
 *
 * Controls the rollout of marketplace-dynamic-pricing functionality.
 */
final class MarketplaceDynamicPricing extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'marketplace';
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
