<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PersonalDevAICoach Feature
 *
 * Controls the rollout of personal-dev-a-i-coach functionality.
 */
final class PersonalDevAICoach extends BaseVerticalFeature
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
