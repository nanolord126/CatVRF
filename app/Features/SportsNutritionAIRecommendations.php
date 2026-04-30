<?php

declare(strict_types=1);

namespace App\Features;

/**
 * SportsNutritionAIRecommendations Feature
 *
 * Controls the rollout of sports-nutrition-a-i-recommendations functionality.
 */
final class SportsNutritionAIRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'sports';
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
