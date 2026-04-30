<?php

declare(strict_types=1);

namespace App\Domains\Electronics\DTOs;

final readonly class OrderProcessDto
{
    public function __construct(
        public int $userId,
        public array $items,
        public string $mode = 'b2c',
        public ?string $businessId = null,
        public ?string $promoCode = null,
        public string $correlationId = '',
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'items' => $this->items,
            'mode' => $this->mode,
            'business_id' => $this->businessId,
            'promo_code' => $this->promoCode,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            userId: $data['user_id'],
            items: $data['items'],
            mode: $data['mode'] ?? 'b2c',
            businessId: $data['business_id'] ?? null,
            promoCode: $data['promo_code'] ?? null,
            correlationId: $data['correlation_id'] ?? '',
        );
    }
}
