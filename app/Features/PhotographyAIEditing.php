<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PhotographyAIEditing Feature
 *
 * Controls the rollout of photography-a-i-editing functionality.
 */
final class PhotographyAIEditing extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'photography';
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
