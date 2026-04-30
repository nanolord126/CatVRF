<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Domain\Events;

use App\Shared\Domain\Events\DomainEvent;
use Ramsey\Uuid\Uuid;

final class OrderCreatedDomainEvent extends DomainEvent
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $restaurantId,
        private readonly string $userId,
        private readonly float $totalAmount,
        private readonly string $deliveryType,
        private readonly \DateTimeImmutable $scheduledFor,
        mixed $correlationId = null,
    ) {
        parent::__construct($correlationId ?? Uuid::uuid4()->toString());
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getRestaurantId(): string
    {
        return $this->restaurantId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getDeliveryType(): string
    {
        return $this->deliveryType;
    }

    public function getScheduledFor(): \DateTimeImmutable
    {
        return $this->scheduledFor;
    }

    public function eventName(): string
    {
        return 'food.order.created';
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'restaurant_id' => $this->restaurantId,
            'user_id' => $this->userId,
            'total_amount' => $this->totalAmount,
            'delivery_type' => $this->deliveryType,
            'scheduled_for' => $this->scheduledFor->format(DATE_ATOM),
            'correlation_id' => $this->getCorrelationId(),
        ];
    }
}
