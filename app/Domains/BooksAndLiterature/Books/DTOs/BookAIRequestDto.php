<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\DTOs;

/**
     * BookAIRequestDto (Layer 2/9)
     * DTO for the AI Book Constructor request.
     */
final readonly class BookAIRequestDto implements BooksDtoInterface
{
        public function __construct(
            public int $userId,
            private array $preferredGenres = [],
            private string $currentMood = 'curious',
            private ?string $biographyFocus = null, // e.g. "Space" or "History"
            private int $readingLevel = 5, // 1-10
            private ?string $correlationId = null
        ) {}
    }
