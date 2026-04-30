<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ConsultingExpertRecommendations Feature
 *
 * Controls the rollout of consulting-expert-recommendations functionality.
 */
final class ConsultingExpertRecommendations extends BaseVerticalFeature
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
