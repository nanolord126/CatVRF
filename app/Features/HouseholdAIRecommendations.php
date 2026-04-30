<?php

declare(strict_types=1);

namespace App\Features;

/**
 * HouseholdAIRecommendations Feature
 *
 * Controls the rollout of household-a-i-recommendations functionality.
 */
final class HouseholdAIRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'household';
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
