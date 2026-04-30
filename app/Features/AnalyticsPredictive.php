<?php

declare(strict_types=1);

namespace App\Features;

/**
 * AnalyticsPredictive Feature
 *
 * Controls the rollout of analytics-predictive functionality.
 */
final class AnalyticsPredictive extends BaseVerticalFeature
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
