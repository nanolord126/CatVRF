<?php

declare(strict_types=1);

namespace App\Domains\Garden\SubVerticals\Gardening\DTOs;

final readonly class ReviewInputDto
{
    public function __construct(
        public int $productId,
        public int $userId,
        public int $rating,
        public string $comment,
        public ?array $growthHistory = null,
        public string $correlationId = '',
    ) {}

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'user_id' => $this->userId,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'growth_history' => $this->growthHistory,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            productId: $data['product_id'],
            userId: $data['user_id'],
            rating: $data['rating'],
            comment: $data['comment'],
            growthHistory: $data['growth_history'] ?? null,
            correlationId: $data['correlation_id'] ?? '',
        );
    }
}
