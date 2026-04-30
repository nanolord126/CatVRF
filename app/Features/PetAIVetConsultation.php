<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PetAIVetConsultation Feature
 *
 * Controls the rollout of pet-a-i-vet-consultation functionality.
 */
final class PetAIVetConsultation extends BaseVerticalFeature
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
