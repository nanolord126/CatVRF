<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ContentAIGeneration Feature
 *
 * Controls the rollout of content-a-i-generation functionality.
 */
final class ContentAIGeneration extends BaseVerticalFeature
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
