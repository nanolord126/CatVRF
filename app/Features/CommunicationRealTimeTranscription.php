<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CommunicationRealTimeTranscription Feature
 *
 * Controls the rollout of communication-real-time-transcription functionality.
 */
final class CommunicationRealTimeTranscription extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'communication';
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
