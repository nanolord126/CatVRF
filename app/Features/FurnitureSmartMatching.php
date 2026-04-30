<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FurnitureSmartMatching Feature
 *
 * Controls the rollout of furniture-smart-matching functionality.
 */
final class FurnitureSmartMatching extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'furniture';
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
