<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ElectronicsPriceTracking Feature
 *
 * Controls the rollout of electronics-price-tracking functionality.
 */
final class ElectronicsPriceTracking extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'electronics';
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
