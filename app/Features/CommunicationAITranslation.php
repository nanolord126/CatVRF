<?php

declare(strict_types=1);

namespace App\Features;

/**
 * CommunicationAITranslation Feature
 *
 * Controls the rollout of communication-a-i-translation functionality.
 */
final class CommunicationAITranslation extends BaseVerticalFeature
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
