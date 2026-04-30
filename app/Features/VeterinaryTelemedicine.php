<?php

declare(strict_types=1);

namespace App\Features;

/**
 * VeterinaryTelemedicine Feature
 *
 * Controls the rollout of veterinary-telemedicine functionality.
 */
final class VeterinaryTelemedicine extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'veterinary';
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
