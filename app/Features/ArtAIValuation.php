<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ArtAIValuation Feature
 *
 * Controls the rollout of art-a-i-valuation functionality.
 */
final class ArtAIValuation extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'art';
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
