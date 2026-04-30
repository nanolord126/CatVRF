<?php

declare(strict_types=1);

namespace App\Features;

/**
 * VeterinaryAppointmentOptimization Feature
 *
 * Controls the rollout of veterinary-appointment-optimization functionality.
 */
final class VeterinaryAppointmentOptimization extends BaseVerticalFeature
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
