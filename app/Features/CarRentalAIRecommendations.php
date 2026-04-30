<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CarRentalAIRecommendations Feature
 *
 * Controls the rollout of car-rental-a-i-recommendations functionality.
 */
final class CarRentalAIRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'carrental';
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
