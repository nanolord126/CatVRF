<?php

declare(strict_types=1);

namespace App\Features;

/**
 * MusicInstrumentMatching Feature
 *
 * Controls the rollout of music-instrument-matching functionality.
 */
final class MusicInstrumentMatching extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'music';
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
