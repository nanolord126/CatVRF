<?php

declare(strict_types=1);

namespace App\Features;

/**
 * AnalyticsRealTime Feature
 *
 * Controls the rollout of analytics-real-time functionality.
 */
final class AnalyticsRealTime extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'analytics';
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
