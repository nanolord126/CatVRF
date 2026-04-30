<?php

declare(strict_types=1);

namespace App\Features;

/**
 * TravelRealTimeAlerts Feature
 *
 * Controls the rollout of travel-real-time-alerts functionality.
 */
final class TravelRealTimeAlerts extends BaseVerticalFeature
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
