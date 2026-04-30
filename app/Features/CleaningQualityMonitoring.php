<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CleaningQualityMonitoring Feature
 *
 * Controls the rollout of cleaning-quality-monitoring functionality.
 */
final class CleaningQualityMonitoring extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'cleaning';
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
