<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ArtArtistMatching Feature
 *
 * Controls the rollout of art-artist-matching functionality.
 */
final class ArtArtistMatching extends BaseVerticalFeature
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
