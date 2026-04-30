<?php

declare(strict_types=1);

namespace App\Features;

/**
 * EducationContentGeneration Feature
 *
 * Controls the rollout of education-content-generation functionality.
 */
final class EducationContentGeneration extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'education';
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
