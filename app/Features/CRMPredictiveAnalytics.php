<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CRMPredictiveAnalytics Feature
 *
 * Controls the rollout of c-r-m-predictive-analytics functionality.
 */
final class CRMPredictiveAnalytics extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'crm';
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
