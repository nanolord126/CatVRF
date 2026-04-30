<?php

declare(strict_types=1);

namespace App\Features;

/**
 * VeterinaryAIDiagnosis Feature
 *
 * Controls the rollout of veterinary-a-i-diagnosis functionality.
 */
final class VeterinaryAIDiagnosis extends BaseVerticalFeature
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
