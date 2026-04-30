<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Flowers\Domain\Enums\OrderStatus;

final readonly class Order
{
    public function __construct(
        public int $id,
        public int $venueId,
        public int $clientId,
        public ?int $floristId,
        public int $tenantId,
        public string $orderNumber,
        public OrderStatus $status,
        public string $deliveryType,
        public ?int $deliverySlotId,
        public ?CarbonImmutable $deliveryDate,
        public string $recipientName,
        public string $recipientPhone,
        public ?string $deliveryAddress,
        public ?string $deliveryInstructions,
        public float $subtotal,
        public float $deliveryFee,
        public float $discountAmount,
        public float $totalAmount,
        public string $currency,
        public string $paymentStatus,
        public ?int $paymentId,
        public ?string $cardMessage,
        public ?string $notes,
        public string $source,
        public bool $isUrgent,
        public bool $isCorporate,
        public int $loyaltyPointsEarned,
        public int $loyaltyPointsUsed,
        public ?CarbonImmutable $confirmedAt,
        public ?CarbonImmutable $assemblyStartedAt,
        public ?CarbonImmutable $assembledAt,
        public ?CarbonImmutable $qualityCheckedAt,
        public ?CarbonImmutable $deliveredAt,
        public ?CarbonImmutable $cancelledAt,
        public ?string $cancellationReason,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $venueId,
        int $clientId,
        int $tenantId,
        string $orderNumber,
        string $recipientName,
        string $recipientPhone,
        float $totalAmount,
        string $deliveryType = 'delivery',
        ?string $deliveryAddress = null,
        ?int $deliverySlotId = null,
        ?CarbonImmutable $deliveryDate = null,
        string $source = 'website',
    ): self {
        return new self(
            id: 0,
            venueId: $venueId,
            clientId: $clientId,
            floristId: null,
            tenantId: $tenantId,
            orderNumber: $orderNumber,
            status: OrderStatus::PENDING,
            deliveryType: $deliveryType,
            deliverySlotId: $deliverySlotId,
            deliveryDate: $deliveryDate,
            recipientName: $recipientName,
            recipientPhone: $recipientPhone,
            deliveryAddress: $deliveryAddress,
            deliveryInstructions: null,
            subtotal: $totalAmount,
            deliveryFee: 0,
            discountAmount: 0,
            totalAmount: $totalAmount,
            currency: 'RUB',
            paymentStatus: 'pending',
            paymentId: null,
            cardMessage: null,
            notes: null,
            source: $source,
            isUrgent: false,
            isCorporate: false,
            loyaltyPointsEarned: 0,
            loyaltyPointsUsed: 0,
            confirmedAt: null,
            assemblyStartedAt: null,
            assembledAt: null,
            qualityCheckedAt: null,
            deliveredAt: null,
            cancelledAt: null,
            cancellationReason: null,
            metadata: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function canTransitionTo(OrderStatus $target): bool
    {
        return $this->status->canTransitionTo($target);
    }

    public function transitionTo(OrderStatus $target, ?string $reason = null): self
    {
        if (!$this->canTransitionTo($target)) {
            throw new \RuntimeException("Cannot transition from {$this->status->value} to {$target->value}");
        }

        $now = CarbonImmutable::now();
        $confirmedAt = $this->confirmedAt;
        $assemblyStartedAt = $this->assemblyStartedAt;
        $assembledAt = $this->assembledAt;
        $qualityCheckedAt = $this->qualityCheckedAt;
        $deliveredAt = $this->deliveredAt;
        $cancelledAt = $this->cancelledAt;
        $cancellationReason = $this->cancellationReason;

        return match ($target) {
            OrderStatus::CONFIRMED => new self(
                ...$this->toArray(),
                status: $target,
                confirmedAt: $now,
                updatedAt: $now,
            ),
            OrderStatus::IN_ASSEMBLY => new self(
                ...$this->toArray(),
                status: $target,
                assemblyStartedAt: $now,
                updatedAt: $now,
            ),
            OrderStatus::ASSEMBLED => new self(
                ...$this->toArray(),
                status: $target,
                assembledAt: $now,
                updatedAt: $now,
            ),
            OrderStatus::QUALITY_CHECKED => new self(
                ...$this->toArray(),
                status: $target,
                qualityCheckedAt: $now,
                updatedAt: $now,
            ),
            OrderStatus::OUT_FOR_DELIVERY, OrderStatus::READY_FOR_DELIVERY => new self(
                ...$this->toArray(),
                status: $target,
                updatedAt: $now,
            ),
            OrderStatus::DELIVERED, OrderStatus::PICKED_UP => new self(
                ...$this->toArray(),
                status: $target,
                deliveredAt: $now,
                updatedAt: $now,
            ),
            OrderStatus::CANCELLED => new self(
                ...$this->toArray(),
                status: $target,
                cancelledAt: $now,
                cancellationReason: $reason,
                updatedAt: $now,
            ),
            OrderStatus::REFUNDED => new self(
                ...$this->toArray(),
                status: $target,
                updatedAt: $now,
            ),
            default => throw new \RuntimeException("Invalid transition to {$target->value}"),
        };
    }

    public function assignFlorist(int $floristId): self
    {
        return new self(
            ...$this->toArray(),
            floristId: $floristId,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isPaid(): bool
    {
        return $this->paymentStatus === 'paid';
    }

    public function isCancellable(): bool
    {
        return $this->status->isCancellable();
    }

    public function isOverdue(): bool
    {
        if (!$this->deliveryDate || $this->status->isFinal()) {
            return false;
        }

        return $this->deliveryDate->isPast();
    }

    public function getAssemblyDuration(): ?int
    {
        if (!$this->assemblyStartedAt || !$this->assembledAt) {
            return null;
        }

        return $this->assemblyStartedAt->diffInMinutes($this->assembledAt);
    }

    private function toArray(): array
    {
        return get_object_vars($this);
    }
}
