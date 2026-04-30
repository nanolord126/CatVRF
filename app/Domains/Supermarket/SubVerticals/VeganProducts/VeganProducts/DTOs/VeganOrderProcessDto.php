<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\VeganProducts\DTOs;

final readonly class VeganOrderProcessDto
{
    public function __construct(
        public int $userId,
        public int $productId,
        public int $quantity,
        public bool $isB2B = false,
        public ?string $correlationId = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'is_b2b' => $this->isB2B,
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            userId: $data['user_id'],
            productId: $data['product_id'],
            quantity: $data['quantity'],
            isB2B: $data['is_b2b'] ?? false,
            correlationId: $data['correlation_id'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
