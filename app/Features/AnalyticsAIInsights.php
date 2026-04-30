<?php

declare(strict_types=1);

namespace App\Features;

/**
 * AnalyticsAIInsights Feature
 *
 * Controls the rollout of analytics-a-i-insights functionality.
 */
final class AnalyticsAIInsights extends BaseVerticalFeature
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
