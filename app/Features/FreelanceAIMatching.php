<?php

declare(strict_types=1);

namespace App\Features;

/**
 * FreelanceAIMatching Feature
 *
 * Controls the rollout of freelance-a-i-matching functionality.
 */
final class FreelanceAIMatching extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'freelance';
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
