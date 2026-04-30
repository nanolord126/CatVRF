<?php

declare(strict_types=1);

namespace App\Features;

/**
 * TravelDynamicPricing Feature
 *
 * Controls the rollout of travel-dynamic-pricing functionality.
 */
final class TravelDynamicPricing extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'travel';
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
