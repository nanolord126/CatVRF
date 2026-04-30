<?php

declare(strict_types=1);

namespace App\Features;

/**
 * LogisticsAIScheduling Feature
 *
 * Controls the rollout of logistics-a-i-scheduling functionality.
 */
final class LogisticsAIScheduling extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'logistics';
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
