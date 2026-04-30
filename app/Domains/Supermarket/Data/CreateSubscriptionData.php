<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Data;

use Illuminate\Contracts\Support\Arrayable;
use Spatie\LaravelData\Data;

final class CreateSubscriptionData extends Data implements Arrayable
{
    public function __construct(
        public readonly int $buyer_id,
        public readonly int $seller_id,
        public readonly array $items,
        public readonly string $frequency,
        public readonly string $delivery_day,
        public readonly ?string $delivery_time_slot,
        public readonly int $address_id,
        public readonly string $payment_method,
        public readonly ?string $promo_code,
        public readonly ?bool $pay_now,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            buyer_id: $data['buyer_id'],
            seller_id: $data['seller_id'],
            items: $data['items'],
            frequency: $data['frequency'],
            delivery_day: $data['delivery_day'],
            delivery_time_slot: $data['delivery_time_slot'] ?? null,
            address_id: $data['address_id'],
            payment_method: $data['payment_method'],
            promo_code: $data['promo_code'] ?? null,
            pay_now: $data['pay_now'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'buyer_id' => $this->buyer_id,
            'seller_id' => $this->seller_id,
            'items' => $this->items,
            'frequency' => $this->frequency,
            'delivery_day' => $this->delivery_day,
            'delivery_time_slot' => $this->delivery_time_slot,
            'address_id' => $this->address_id,
            'payment_method' => $this->payment_method,
            'promo_code' => $this->promo_code,
            'pay_now' => $this->pay_now,
        ];
    }
}
