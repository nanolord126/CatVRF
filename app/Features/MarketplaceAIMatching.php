<?php

declare(strict_types=1);

namespace App\Features;

/**
 * MarketplaceAIMatching Feature
 *
 * Controls the rollout of marketplace-a-i-matching functionality.
 */
final class MarketplaceAIMatching extends BaseVerticalFeature
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
