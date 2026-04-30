<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CarRentalDynamicPricing Feature
 *
 * Controls the rollout of car-rental-dynamic-pricing functionality.
 */
final class CarRentalDynamicPricing extends BaseVerticalFeature
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
