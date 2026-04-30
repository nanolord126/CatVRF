<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\DTOs;

final readonly class BookReviewDto implements BooksDtoInterface
{
    public function __construct(
        public int $userId,
        public int $bookId,
        public int $rating,
        public ?string $comment = null,
        public array $moodTags = [],
        public ?string $correlationId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'book_id' => $this->bookId,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'mood_tags' => $this->moodTags,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            userId: $data['user_id'],
            bookId: $data['book_id'],
            rating: $data['rating'],
            comment: $data['comment'] ?? null,
            moodTags: $data['mood_tags'] ?? [],
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
