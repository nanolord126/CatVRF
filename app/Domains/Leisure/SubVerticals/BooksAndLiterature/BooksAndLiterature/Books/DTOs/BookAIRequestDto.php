<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\DTOs;

final readonly class BookAIRequestDto implements BooksDtoInterface
{
    public function __construct(
        public int $userId,
        public array $preferredGenres = [],
        public string $currentMood = 'curious',
        public ?string $biographyFocus = null,
        public int $readingLevel = 5,
        public ?string $correlationId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'preferred_genres' => $this->preferredGenres,
            'current_mood' => $this->currentMood,
            'biography_focus' => $this->biographyFocus,
            'reading_level' => $this->readingLevel,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            userId: $data['user_id'],
            preferredGenres: $data['preferred_genres'] ?? [],
            currentMood: $data['current_mood'] ?? 'curious',
            biographyFocus: $data['biography_focus'] ?? null,
            readingLevel: $data['reading_level'] ?? 5,
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
