<?php

declare(strict_types=1);

namespace App\Features;

/**
 * SportsLiveBetting Feature
 *
 * Controls the rollout of sports-live-betting functionality.
 */
final class SportsLiveBetting extends BaseVerticalFeature
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
