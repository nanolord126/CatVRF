<?php

declare(strict_types=1);

namespace App\Features;

/**
 * LogisticsRealTimeTracking Feature
 *
 * Controls the rollout of logistics-real-time-tracking functionality.
 */
final class LogisticsRealTimeTracking extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'logistics';
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
