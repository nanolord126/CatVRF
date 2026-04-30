<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ConsultingAIMatching Feature
 *
 * Controls the rollout of consulting-a-i-matching functionality.
 */
final class ConsultingAIMatching extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'consulting';
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
