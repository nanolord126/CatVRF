<?php

declare(strict_types=1);

namespace App\Domains\Garden\SubVerticals\Gardening\DTOs;

final readonly class SubscriptionBoxDto
{
    public function __construct(
        public string $name,
        public string $frequency,
        public int $price,
        public array $contents,
        public bool $isActive = true,
        public ?string $correlationId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'frequency' => $this->frequency,
            'price' => $this->price,
            'contents' => $this->contents,
            'is_active' => $this->isActive,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            name: $data['name'],
            frequency: $data['frequency'],
            price: $data['price'],
            contents: $data['contents'],
            isActive: $data['is_active'] ?? true,
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
