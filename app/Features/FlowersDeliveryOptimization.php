<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FlowersDeliveryOptimization Feature
 *
 * Controls the rollout of flowers-delivery-optimization functionality.
 */
final class FlowersDeliveryOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'flowers';
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
