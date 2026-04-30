<?php

declare(strict_types=1);

namespace App\Features;

/**
 * HotelsSmartCheckin Feature
 *
 * Controls the rollout of hotels-smart-checkin functionality.
 */
final class HotelsSmartCheckin extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'hotels';
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
