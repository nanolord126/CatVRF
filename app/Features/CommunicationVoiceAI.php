<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CommunicationVoiceAI Feature
 *
 * Controls the rollout of communication-voice-a-i functionality.
 */
final class CommunicationVoiceAI extends BaseVerticalFeature
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
