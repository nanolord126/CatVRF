<?php

declare(strict_types=1);

namespace App\Features;

/**
 * BooksAIRecommendations Feature
 *
 * Controls the rollout of books-a-i-recommendations functionality.
 */
final class BooksAIRecommendations extends BaseVerticalFeature
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
