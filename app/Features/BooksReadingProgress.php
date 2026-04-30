<?php

declare(strict_types=1);

namespace App\Features;

/**
 * BooksReadingProgress Feature
 *
 * Controls the rollout of books-reading-progress functionality.
 */
final class BooksReadingProgress extends BaseVerticalFeature
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
