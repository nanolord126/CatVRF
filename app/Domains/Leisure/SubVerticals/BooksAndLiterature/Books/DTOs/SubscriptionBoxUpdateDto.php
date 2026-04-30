<?php

declare(strict_types=1);

namespace App\Domains\BooksAndLiterature\Books\DTOs;

final readonly class SubscriptionBoxUpdateDto implements BooksDtoInterface
{
    public function __construct(
        public int $boxId,
        public string $name,
        public int $priceMonthly,
        public array $genreFocus = [],
        public ?string $correlationId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'box_id' => $this->boxId,
            'name' => $this->name,
            'price_monthly' => $this->priceMonthly,
            'genre_focus' => $this->genreFocus,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            boxId: $data['box_id'],
            name: $data['name'],
            priceMonthly: $data['price_monthly'],
            genreFocus: $data['genre_focus'] ?? [],
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
