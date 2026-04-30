<?php

declare(strict_types=1);

namespace App\Features;

/**
 * AutoPredictiveMaintenance Feature
 *
 * Controls the rollout of auto-predictive-maintenance functionality.
 */
final class AutoPredictiveMaintenance extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'auto';
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
