<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PhotographyStyleTransfer Feature
 *
 * Controls the rollout of photography-style-transfer functionality.
 */
final class PhotographyStyleTransfer extends BaseVerticalFeature
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
