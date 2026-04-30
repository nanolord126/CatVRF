<?php

declare(strict_types=1);

namespace App\Features;

/**
 * DynamicPricingML Feature
 *
 * Controls the rollout of dynamic-pricing-m-l functionality.
 */
final class DynamicPricingML extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'common';
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
