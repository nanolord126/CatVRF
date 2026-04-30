<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CarRentalSmartCheckin Feature
 *
 * Controls the rollout of car-rental-smart-checkin functionality.
 */
final class CarRentalSmartCheckin extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'carrental';
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
