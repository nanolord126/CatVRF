<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CollectiblesMarketAnalysis Feature
 *
 * Controls the rollout of collectibles-market-analysis functionality.
 */
final class CollectiblesMarketAnalysis extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'collectibles';
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
