<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\DTOs;

use Modules\Supermarket\Domain\Enums\SubscriptionFrequency;
use Illuminate\Support\Collection;

readonly class CreateSubscriptionData
{
    public function __construct(
        public int $buyerId,
        public int $sellerId,
        public SubscriptionFrequency $frequency,
        public int $deliveryDay,
        public string $deliveryTimeSlot,
        public int $addressId,
        public string $paymentMethod,
        public Collection $items,
        public ?string $promoCode = null,
        public bool $payNow = false,
        public ?string $name = null,
    ) {
        if ($items->isEmpty()) {
            throw new \InvalidArgumentException('Subscription must have at least one item');
        }

        if (!in_array($deliveryDay, range(0, 7), true)) {
            throw new \InvalidArgumentException('Invalid delivery day');
        }

        if (!in_array($deliveryTimeSlot, ['morning', 'day', 'evening'], true)) {
            throw new \InvalidArgumentException('Invalid delivery time slot');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            buyerId: $data['buyer_id'],
            sellerId: $data['seller_id'],
            frequency: SubscriptionFrequency::from($data['frequency']),
            deliveryDay: $data['delivery_day'],
            deliveryTimeSlot: $data['delivery_time_slot'],
            addressId: $data['address_id'],
            paymentMethod: $data['payment_method'],
            items: collect($data['items']),
            promoCode: $data['promo_code'] ?? null,
            payNow: $data['pay_now'] ?? false,
            name: $data['name'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'frequency' => $this->frequency->value,
            'delivery_day' => $this->deliveryDay,
            'delivery_time_slot' => $this->deliveryTimeSlot,
            'address_id' => $this->addressId,
            'payment_method' => $this->paymentMethod,
            'items' => $this->items->toArray(),
            'promo_code' => $this->promoCode,
            'pay_now' => $this->payNow,
            'name' => $this->name,
        ];
    }
}
