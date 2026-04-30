<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Entities;

use Modules\Supermarket\Domain\Enums\SubscriptionFrequency;
use Modules\Supermarket\Domain\Enums\SubscriptionStatus;
use Modules\Supermarket\Domain\ValueObjects\Money;
use Modules\Supermarket\Domain\ValueObjects\DeliverySlot;
use Carbon\Carbon;

final readonly class Subscription
{
    private function __construct(
        public int $id,
        public int $buyerId,
        public int $sellerId,
        public SubscriptionStatus $status,
        public SubscriptionFrequency $frequency,
        public int $deliveryDay,
        public Carbon $nextDeliveryAt,
        public Money $totalAmount,
        public bool $isB2b,
        public ?Carbon $pausedUntil,
        public ?string $cancelReason,
        public ?Carbon $cancelledAt,
        public Carbon $createdAt,
        public Carbon $updatedAt,
    ) {}

    public static function create(
        int $buyerId,
        int $sellerId,
        SubscriptionFrequency $frequency,
        int $deliveryDay,
        Money $totalAmount,
        bool $isB2b = false,
    ): self {
        return new self(
            id: 0,
            buyerId: $buyerId,
            sellerId: $sellerId,
            status: SubscriptionStatus::ACTIVE,
            frequency: $frequency,
            deliveryDay: $deliveryDay,
            nextDeliveryAt: self::calculateNextDeliveryDate($frequency, $deliveryDay),
            totalAmount: $totalAmount,
            isB2b: $isB2b,
            pausedUntil: null,
            cancelReason: null,
            cancelledAt: null,
            createdAt: now(),
            updatedAt: now(),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            buyerId: $data['buyer_id'],
            sellerId: $data['seller_id'],
            status: SubscriptionStatus::from($data['status']),
            frequency: SubscriptionFrequency::from($data['frequency']),
            deliveryDay: $data['delivery_day'],
            nextDeliveryAt: Carbon::parse($data['next_delivery_at']),
            totalAmount: Money::fromFloat($data['total_amount']),
            isB2b: (bool) $data['is_b2b'],
            pausedUntil: $data['paused_until'] ? Carbon::parse($data['paused_until']) : null,
            cancelReason: $data['cancel_reason'] ?? null,
            cancelledAt: $data['cancelled_at'] ? Carbon::parse($data['cancelled_at']) : null,
            createdAt: Carbon::parse($data['created_at']),
            updatedAt: Carbon::parse($data['updated_at']),
        );
    }

    public function pause(int $days): self
    {
        return new self(
            id: $this->id,
            buyerId: $this->buyerId,
            sellerId: $this->sellerId,
            status: SubscriptionStatus::PAUSED,
            frequency: $this->frequency,
            deliveryDay: $this->deliveryDay,
            nextDeliveryAt: $this->nextDeliveryAt->addDays($days),
            totalAmount: $this->totalAmount,
            isB2b: $this->isB2b,
            pausedUntil: now()->addDays($days),
            cancelReason: $this->cancelReason,
            cancelledAt: $this->cancelledAt,
            createdAt: $this->createdAt,
            updatedAt: now(),
        );
    }

    public function resume(): self
    {
        return new self(
            id: $this->id,
            buyerId: $this->buyerId,
            sellerId: $this->sellerId,
            status: SubscriptionStatus::ACTIVE,
            frequency: $this->frequency,
            deliveryDay: $this->deliveryDay,
            nextDeliveryAt: self::calculateNextDeliveryDate($this->frequency, $this->deliveryDay),
            totalAmount: $this->totalAmount,
            isB2b: $this->isB2b,
            pausedUntil: null,
            cancelReason: $this->cancelReason,
            cancelledAt: $this->cancelledAt,
            createdAt: $this->createdAt,
            updatedAt: now(),
        );
    }

    public function cancel(?string $reason = null): self
    {
        return new self(
            id: $this->id,
            buyerId: $this->buyerId,
            sellerId: $this->sellerId,
            status: SubscriptionStatus::CANCELLED,
            frequency: $this->frequency,
            deliveryDay: $this->deliveryDay,
            nextDeliveryAt: $this->nextDeliveryAt,
            totalAmount: $this->totalAmount,
            isB2b: $this->isB2b,
            pausedUntil: $this->pausedUntil,
            cancelReason: $reason,
            cancelledAt: now(),
            createdAt: $this->createdAt,
            updatedAt: now(),
        );
    }

    public function updateNextDelivery(): self
    {
        return new self(
            id: $this->id,
            buyerId: $this->buyerId,
            sellerId: $this->sellerId,
            status: $this->status,
            frequency: $this->frequency,
            deliveryDay: $this->deliveryDay,
            nextDeliveryAt: self::calculateNextDeliveryDate($this->frequency, $this->deliveryDay),
            totalAmount: $this->totalAmount,
            isB2b: $this->isB2b,
            pausedUntil: $this->pausedUntil,
            cancelReason: $this->cancelReason,
            cancelledAt: $this->cancelledAt,
            createdAt: $this->createdAt,
            updatedAt: now(),
        );
    }

    public function updateTotalAmount(Money $newAmount): self
    {
        return new self(
            id: $this->id,
            buyerId: $this->buyerId,
            sellerId: $this->sellerId,
            status: $this->status,
            frequency: $this->frequency,
            deliveryDay: $this->deliveryDay,
            nextDeliveryAt: $this->nextDeliveryAt,
            totalAmount: $newAmount,
            isB2b: $this->isB2b,
            pausedUntil: $this->pausedUntil,
            cancelReason: $this->cancelReason,
            cancelledAt: $this->cancelledAt,
            createdAt: $this->createdAt,
            updatedAt: now(),
        );
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE;
    }

    public function isPaused(): bool
    {
        return $this->status === SubscriptionStatus::PAUSED;
    }

    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatus::CANCELLED;
    }

    public function isDueForDelivery(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        return $this->nextDeliveryAt->lte(now()->addHours(2));
    }

    private static function calculateNextDeliveryDate(SubscriptionFrequency $frequency, int $deliveryDay): Carbon
    {
        return match ($frequency) {
            SubscriptionFrequency::WEEKLY => now()->next($deliveryDay),
            SubscriptionFrequency::BIWEEKLY => now()->addWeek()->next($deliveryDay),
            SubscriptionFrequency::MONTHLY => now()->addMonth()->day($deliveryDay),
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'status' => $this->status->value,
            'frequency' => $this->frequency->value,
            'delivery_day' => $this->deliveryDay,
            'next_delivery_at' => $this->nextDeliveryAt->toIso8601String(),
            'total_amount' => $this->totalAmount->toFloat(),
            'is_b2b' => $this->isB2b,
            'paused_until' => $this->pausedUntil?->toIso8601String(),
            'cancel_reason' => $this->cancelReason,
            'cancelled_at' => $this->cancelledAt?->toIso8601String(),
            'created_at' => $this->createdAt->toIso8601String(),
            'updated_at' => $this->updatedAt->toIso8601String(),
        ];
    }
}
