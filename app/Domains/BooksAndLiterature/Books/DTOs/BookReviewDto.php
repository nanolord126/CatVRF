<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\DTOs;

/**
     * BookReviewDto (Layer 2/9)
     */
final readonly class BookReviewDto implements BooksDtoInterface
{
        public function __construct(
            public int $userId,
            public int $bookId,
            public int $rating,
            private ?string $comment = null,
            private array $moodTags = [],
            private ?string $correlationId = null
        ) {}
}
