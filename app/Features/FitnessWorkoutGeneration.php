<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FitnessWorkoutGeneration Feature
 *
 * Controls the rollout of fitness-workout-generation functionality.
 */
final class FitnessWorkoutGeneration extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'fitness';
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
