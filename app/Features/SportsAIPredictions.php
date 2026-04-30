<?php

declare(strict_types=1);

namespace App\Features;

/**
 * SportsAIPredictions Feature
 *
 * Controls the rollout of sports-a-i-predictions functionality.
 */
final class SportsAIPredictions extends BaseVerticalFeature
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
