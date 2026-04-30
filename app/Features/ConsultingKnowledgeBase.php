<?php

declare(strict_types=1);

namespace App\Features;

/**
 * ConsultingKnowledgeBase Feature
 *
 * Controls the rollout of consulting-knowledge-base functionality.
 */
final class ConsultingKnowledgeBase extends BaseVerticalFeature
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
