<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\DTOs;

/**
     * BookSaveDto (Layer 2/9)
     * Immutable data transfer object for saving or updating books.
     */
final readonly class BookSaveDto implements BooksDtoInterface
{
        public function __construct(
            public string $title,
            public string $isbn,
            public int $authorId,
            public int $genreId,
            public int $storeId,
            public string $format,
            public int $priceB2c,
            public int $priceB2b,
            public int $stockQuantity,
            public ?string $description = null,
            private ?int $pageCount = null,
            private string $language = 'ru',
            private array $metadata = [], // Mood, Era, AgeRating
            private array $tags = [],
            private bool $isActive = true,
            private ?string $correlationId = null
        ) {}
    }
