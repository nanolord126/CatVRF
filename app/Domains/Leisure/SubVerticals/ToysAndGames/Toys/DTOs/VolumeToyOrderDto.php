<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\ToysAndGames\Toys\DTOs;

final readonly class VolumeToyOrderDto
{
    public function __construct(
        public int $companyId,
        public int $storeId,
        public array $items,
        public string $correlationId,
        public bool $giftPackaging = false,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'store_id' => $this->storeId,
            'items' => $this->items,
            'correlation_id' => $this->correlationId,
            'gift_packaging' => $this->giftPackaging,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            companyId: $data['company_id'],
            storeId: $data['store_id'],
            items: $data['items'],
            correlationId: $data['correlation_id'],
            giftPackaging: $data['gift_packaging'] ?? false,
            metadata: $data['metadata'] ?? [],
        );
    }
}
