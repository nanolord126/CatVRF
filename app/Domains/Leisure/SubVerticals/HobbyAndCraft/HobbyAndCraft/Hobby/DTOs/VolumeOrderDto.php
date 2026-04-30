<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\HobbyAndCraft\Hobby\DTOs;

final readonly class VolumeOrderDto
{
    public function __construct(
        public int $userId,
        public int $productId,
        public int $quantity,
        public bool $applyTaxExemption = false,
        public string $correlationId = '',
    ) {
        if ($this->quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'apply_tax_exemption' => $this->applyTaxExemption,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            userId: $data['user_id'],
            productId: $data['product_id'],
            quantity: $data['quantity'],
            applyTaxExemption: $data['apply_tax_exemption'] ?? false,
            correlationId: $data['correlation_id'] ?? '',
        );
    }
}
