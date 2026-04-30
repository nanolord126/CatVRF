<?php

declare(strict_types=1);

namespace App\Features;

/**
 * HomeServicesAIMatching Feature
 *
 * Controls the rollout of home-services-a-i-matching functionality.
 */
final class HomeServicesAIMatching extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'homeservices';
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
