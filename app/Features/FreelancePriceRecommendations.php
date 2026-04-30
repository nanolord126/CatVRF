<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FreelancePriceRecommendations Feature
 *
 * Controls the rollout of freelance-price-recommendations functionality.
 */
final class FreelancePriceRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'freelance';
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
