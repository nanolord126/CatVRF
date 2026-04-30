<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ContentDistribution Feature
 *
 * Controls the rollout of content-distribution functionality.
 */
final class ContentDistribution extends BaseVerticalFeature
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
