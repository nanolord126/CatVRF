<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ShortTermGuestScreening Feature
 *
 * Controls the rollout of short-term-guest-screening functionality.
 */
final class ShortTermGuestScreening extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'shortterm';
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
