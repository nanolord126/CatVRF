<?php

declare(strict_types=1);

namespace App\Features;

/**
 * SportsRealTimeStats Feature
 *
 * Controls the rollout of sports-real-time-stats functionality.
 */
final class SportsRealTimeStats extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'sports';
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
