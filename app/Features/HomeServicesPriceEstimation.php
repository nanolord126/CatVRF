<?php

declare(strict_types=1);

namespace App\Features;

/**
 * HomeServicesPriceEstimation Feature
 *
 * Controls the rollout of home-services-price-estimation functionality.
 */
final class HomeServicesPriceEstimation extends BaseVerticalFeature
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
