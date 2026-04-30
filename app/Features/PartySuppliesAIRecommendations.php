<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PartySuppliesAIRecommendations Feature
 *
 * Controls the rollout of party-supplies-a-i-recommendations functionality.
 */
final class PartySuppliesAIRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'partysupplies';
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
