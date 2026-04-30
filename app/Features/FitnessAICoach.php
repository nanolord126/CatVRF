<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FitnessAICoach Feature
 *
 * Controls the rollout of fitness-a-i-coach functionality.
 */
final class FitnessAICoach extends BaseVerticalFeature
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
