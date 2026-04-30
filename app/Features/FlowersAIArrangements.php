<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FlowersAIArrangements Feature
 *
 * Controls the rollout of flowers-a-i-arrangements functionality.
 */
final class FlowersAIArrangements extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'flowers';
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
