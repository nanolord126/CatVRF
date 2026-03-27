<?php

declare(strict_types=1);

namespace App\Domains\Fashion\DTO;

/**
 * КАНЬОН 2026 — FASHION ORDER DTO (B2C & B2B)
 * 
 * Обязателен correlation_id. Режим B2B/B2C на уровне DTO.
 */
final readonly class FashionOrderDto
{
    public function __construct(
        public int $tenant_id,
        public int $user_id,
        public int $store_id,
        public array $items,
        public int $total_amount,
        public string $currency = 'RUB',
        public ?string $inn = null, // Только для B2B
        public string $status = 'pending',
        public string $correlation_id = '',
    ) {
        if (empty($this->correlation_id)) {
            throw new \InvalidArgumentException('FashionOrderDto: correlation_id is mandatory');
        }
    }

    /**
     * Создать из массива данных (Request)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int)($data['tenant_id'] ?? 0),
            user_id: (int)($data['user_id'] ?? 0),
            store_id: (int)($data['store_id'] ?? 0),
            items: (array)($data['items'] ?? []),
            total_amount: (int)($data['total_amount'] ?? 0),
            currency: (string)($data['currency'] ?? 'RUB'),
            inn: $data['inn'] ?? null,
            status: (string)($data['status'] ?? 'pending'),
            correlation_id: (string)($data['correlation_id'] ?? \Illuminate\Support\Str::uuid()->toString()),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'store_id' => $this->store_id,
            'items' => $this->items,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'inn' => $this->inn,
            'status' => $this->status,
            'correlation_id' => $this->correlation_id,
        ];
    }
}
