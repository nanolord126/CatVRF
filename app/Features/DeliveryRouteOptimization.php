<?php

declare(strict_types=1);

namespace App\Features;

/**
 * DeliveryRouteOptimization Feature
 *
 * Controls the rollout of delivery-route-optimization functionality.
 */
final class DeliveryRouteOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'delivery';
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
