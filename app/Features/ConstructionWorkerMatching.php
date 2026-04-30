<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ConstructionWorkerMatching Feature
 *
 * Controls the rollout of construction-worker-matching functionality.
 */
final class ConstructionWorkerMatching extends BaseVerticalFeature
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
