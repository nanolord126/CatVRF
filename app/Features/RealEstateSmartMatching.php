<?php

declare(strict_types=1);

namespace App\Features;

/**
 * RealEstateSmartMatching Feature
 *
 * Controls the rollout of real-estate-smart-matching functionality.
 */
final class RealEstateSmartMatching extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'realestate';
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
