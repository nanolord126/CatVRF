<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ToysAgeRecommendations Feature
 *
 * Controls the rollout of toys-age-recommendations functionality.
 */
final class ToysAgeRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'toys';
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
