<?php

declare(strict_types=1);

namespace App\Features;

/**
 * OfficeCateringAIMenu Feature
 *
 * Controls the rollout of office-catering-a-i-menu functionality.
 */
final class OfficeCateringAIMenu extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'officecatering';
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
