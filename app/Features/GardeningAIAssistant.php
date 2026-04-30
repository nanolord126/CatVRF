<?php

declare(strict_types=1);

namespace App\Features;

/**
 * GardeningAIAssistant Feature
 *
 * Controls the rollout of gardening-a-i-assistant functionality.
 */
final class GardeningAIAssistant extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'gardening';
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
