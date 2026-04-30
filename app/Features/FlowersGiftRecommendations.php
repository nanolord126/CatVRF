<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FlowersGiftRecommendations Feature
 *
 * Controls the rollout of flowers-gift-recommendations functionality.
 */
final class FlowersGiftRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'flowers';
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
