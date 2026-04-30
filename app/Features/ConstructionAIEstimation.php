<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ConstructionAIEstimation Feature
 *
 * Controls the rollout of construction-a-i-estimation functionality.
 */
final class ConstructionAIEstimation extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'construction';
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
