<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CleaningAIScheduling Feature
 *
 * Controls the rollout of cleaning-a-i-scheduling functionality.
 */
final class CleaningAIScheduling extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'cleaning';
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
