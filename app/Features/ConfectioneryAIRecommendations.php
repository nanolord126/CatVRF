<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ConfectioneryAIRecommendations Feature
 *
 * Controls the rollout of confectionery-a-i-recommendations functionality.
 */
final class ConfectioneryAIRecommendations extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'confectionery';
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
