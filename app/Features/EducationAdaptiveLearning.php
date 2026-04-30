<?php

declare(strict_types=1);

namespace App\Features;

/**
 * EducationAdaptiveLearning Feature
 *
 * Controls the rollout of education-adaptive-learning functionality.
 */
final class EducationAdaptiveLearning extends BaseVerticalFeature
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
