<?php

declare(strict_types=1);

namespace App\Features;

/**
 * DeliveryRealTimeTracking Feature
 *
 * Controls the rollout of delivery-real-time-tracking functionality.
 */
final class DeliveryRealTimeTracking extends BaseVerticalFeature
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
