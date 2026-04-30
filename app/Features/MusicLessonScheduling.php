<?php

declare(strict_types=1);

namespace App\Features;

/**
 * MusicLessonScheduling Feature
 *
 * Controls the rollout of music-lesson-scheduling functionality.
 */
final class MusicLessonScheduling extends BaseVerticalFeature
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
