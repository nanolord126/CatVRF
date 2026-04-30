<?php

declare(strict_types=1);

namespace App\Features;

/**
 * LegalAIDocumentAnalysis Feature
 *
 * Controls the rollout of legal-a-i-document-analysis functionality.
 */
final class LegalAIDocumentAnalysis extends BaseVerticalFeature
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
