<?php

declare(strict_types=1);

namespace App\Features;

/**
 * LegalContractReview Feature
 *
 * Controls the rollout of legal-contract-review functionality.
 */
final class LegalContractReview extends BaseVerticalFeature
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
