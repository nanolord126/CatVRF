<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FoodMenuAIRecommendations Feature
 *
 * Controls the rollout of food-menu-a-i-recommendations functionality.
 */
final class FoodMenuAIRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'food';
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
