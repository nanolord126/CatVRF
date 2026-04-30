<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PetServiceMatching Feature
 *
 * Controls the rollout of pet-service-matching functionality.
 */
final class PetServiceMatching extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'pet';
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
