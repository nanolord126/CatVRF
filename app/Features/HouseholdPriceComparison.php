<?php

declare(strict_types=1);

namespace App\Features;

/**
 * HouseholdPriceComparison Feature
 *
 * Controls the rollout of household-price-comparison functionality.
 */
final class HouseholdPriceComparison extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'household';
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
