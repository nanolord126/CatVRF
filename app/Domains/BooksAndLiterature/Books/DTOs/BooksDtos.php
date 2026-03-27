<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\BooksAndLiterature\Books\DTOs;

/**
 * Common DTO contract for the Books vertical.
 */
interface BooksDtoInterface {}

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
        public ?int $pageCount = null,
        public string $language = 'ru',
        public array $metadata = [], // Mood, Era, AgeRating
        public array $tags = [],
        public bool $isActive = true,
        public ?string $correlationId = null
    ) {}
}

/**
 * BookAIRequestDto (Layer 2/9)
 * DTO for the AI Book Constructor request.
 */
final readonly class BookAIRequestDto implements BooksDtoInterface
{
    public function __construct(
        public int $userId,
        public array $preferredGenres = [],
        public string $currentMood = 'curious',
        public ?string $biographyFocus = null, // e.g. "Space" or "History"
        public int $readingLevel = 5, // 1-10
        public ?string $correlationId = null
    ) {}
}

/**
 * VolumeOrderDto (Layer 2/9)
 * Specialized DTO for B2B/Corporate orders (Layer 2/9).
 */
final readonly class VolumeOrderDto implements BooksDtoInterface
{
    public function __construct(
        public int $userId,
        public int $tenantId,
        public string $type, // 'institutional', 'b2b'
        public array $items, // [{book_id, qty}]
        public string $shippingAddress,
        public bool $requestInvoice = true,
        public ?string $correlationId = null
    ) {}
}

/**
 * SubscriptionBoxUpdateDto (Layer 2/9)
 */
final readonly class SubscriptionBoxUpdateDto implements BooksDtoInterface
{
    public function __construct(
        public int $boxId,
        public string $name,
        public int $priceMonthly,
        public array $genreFocus = [],
        public ?string $correlationId = null
    ) {}
}

/**
 * BookReviewDto (Layer 2/9)
 */
final readonly class BookReviewDto implements BooksDtoInterface
{
    public function __construct(
        public int $userId,
        public int $bookId,
        public int $rating,
        public ?string $comment = null,
        public array $moodTags = [],
        public ?string $correlationId = null
    ) {}
}
