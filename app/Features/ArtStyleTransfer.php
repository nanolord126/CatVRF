<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ArtStyleTransfer Feature
 *
 * Controls the rollout of art-style-transfer functionality.
 */
final class ArtStyleTransfer extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'art';
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
