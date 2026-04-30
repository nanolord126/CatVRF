<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\DTOs;

use Modules\Supermarket\Domain\Enums\ReturnReason;
use Illuminate\Support\Collection;

readonly class CreateReturnData
{
    public function __construct(
        public int $orderId,
        public int $buyerId,
        public int $sellerId,
        public ReturnReason $reasonType,
        public ?string $comment = null,
        public bool $isColdChain = false,
        public string $returnMethod = 'courier',
        public array $images = [],
        public Collection $items = new Collection(),
    ) {
        if ($items->isEmpty()) {
            throw new \InvalidArgumentException('Return must have at least one item');
        }

        if (!in_array($returnMethod, ['pickup', 'courier', 'self_delivery'], true)) {
            throw new \InvalidArgumentException('Invalid return method');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            orderId: $data['order_id'],
            buyerId: $data['buyer_id'],
            sellerId: $data['seller_id'],
            reasonType: ReturnReason::from($data['reason_type']),
            comment: $data['comment'] ?? null,
            isColdChain: $data['is_cold_chain'] ?? false,
            returnMethod: $data['return_method'] ?? 'courier',
            images: $data['images'] ?? [],
            items: collect($data['items'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'reason_type' => $this->reasonType->value,
            'comment' => $this->comment,
            'is_cold_chain' => $this->isColdChain,
            'return_method' => $this->returnMethod,
            'images' => $this->images,
            'items' => $this->items->toArray(),
        ];
    }
}
