<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ShortTermDynamicPricing Feature
 *
 * Controls the rollout of short-term-dynamic-pricing functionality.
 */
final class ShortTermDynamicPricing extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'shortterm';
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
