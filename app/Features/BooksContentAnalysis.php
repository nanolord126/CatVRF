<?php

declare(strict_types=1);

namespace App\Features;

/**
 * BooksContentAnalysis Feature
 *
 * Controls the rollout of books-content-analysis functionality.
 */
final class BooksContentAnalysis extends BaseVerticalFeature
{
    public function resolve(): bool
    {
        return false;
    }

    protected function getVerticalName(): string
    {
        return 'books';
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
