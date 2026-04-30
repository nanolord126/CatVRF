<?php

declare(strict_types=1);

namespace App\Features;

/**
 * LegalAIResearch Feature
 *
 * Controls the rollout of legal-a-i-research functionality.
 */
final class LegalAIResearch extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'legal';
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
