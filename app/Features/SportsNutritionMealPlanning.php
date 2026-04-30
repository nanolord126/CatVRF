<?php

declare(strict_types=1);

namespace App\Features;

/**
 * SportsNutritionMealPlanning Feature
 *
 * Controls the rollout of sports-nutrition-meal-planning functionality.
 */
final class SportsNutritionMealPlanning extends BaseVerticalFeature
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
