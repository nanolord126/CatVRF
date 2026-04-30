<?php

declare(strict_types=1);

namespace App\Features;

/**
 * HotelsAIRecommendations Feature
 *
 * Controls the rollout of hotels-a-i-recommendations functionality.
 */
final class HotelsAIRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'hotels';
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
