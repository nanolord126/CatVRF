<?php

declare(strict_types=1);

namespace App\Features;

/**
 * PhotographyPortfolioMatching Feature
 *
 * Controls the rollout of photography-portfolio-matching functionality.
 */
final class PhotographyPortfolioMatching extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'photography';
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
