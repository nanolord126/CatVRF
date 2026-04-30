<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CollectiblesAIValuation Feature
 *
 * Controls the rollout of collectibles-a-i-valuation functionality.
 */
final class CollectiblesAIValuation extends BaseVerticalFeature
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
