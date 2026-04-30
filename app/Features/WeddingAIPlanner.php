<?php

declare(strict_types=1);

namespace App\Features;

/**
 * WeddingAIPlanner Feature
 *
 * Controls the rollout of wedding-a-i-planner functionality.
 */
final class WeddingAIPlanner extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'wedding';
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
