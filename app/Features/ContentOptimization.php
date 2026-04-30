<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ContentOptimization Feature
 *
 * Controls the rollout of content-optimization functionality.
 */
final class ContentOptimization extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'content';
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
