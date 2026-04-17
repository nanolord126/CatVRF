<?php declare(strict_types=1);

namespace App\Domains\B2B\DTOs;

final readonly class CreateOrderDto
{
    public function __construct(
        public int $businessGroupId,
        public array $items,
        public string $deliveryAddress,
        public bool $useCredit,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            businessGroupId: $data['business_group_id'],
            items: $data['items'],
            deliveryAddress: $data['delivery_address'],
            useCredit: $data['use_credit'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'business_group_id' => $this->businessGroupId,
            'items' => $this->items,
            'delivery_address' => $this->deliveryAddress,
            'use_credit' => $this->useCredit,
        ];
    }
}
