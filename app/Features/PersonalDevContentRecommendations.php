<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PersonalDevContentRecommendations Feature
 *
 * Controls the rollout of personal-dev-content-recommendations functionality.
 */
final class PersonalDevContentRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'personaldev';
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
