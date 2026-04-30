<?php

declare(strict_types=1);

namespace App\Features;

/**
 * AdCreativeGeneration Feature
 *
 * Controls the rollout of ad-creative-generation functionality.
 */
final class AdCreativeGeneration extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'advertising';
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
