<?php

declare(strict_types=1);

namespace App\Domains\Food\Domain\Entities;

use App\Domains\Food\Domain\Events\OrderReadyForPickup;
use App\Domains\Food\Domain\Events\OrderWasPlaced;
use App\Domains\Food\Domain\ValueObjects\OrderStatus;
use App\Shared\Domain\Entities\AggregateRoot;
use App\Shared\Domain\ValueObjects\Money;
use App\Shared\Domain\ValueObjects\TenantId;
use App\Shared\Domain\ValueObjects\Uuid;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class Order extends AggregateRoot
{
    /**
     * @param Uuid $id
     * @param TenantId $tenantId
     * @param Uuid $restaurantId
     * @param Uuid $clientId
     * @param Collection<OrderItem> $items
     * @param Money $totalPrice
     * @param OrderStatus $status
     * @param Carbon|null $createdAt
     * @param Carbon|null $updatedAt
     * @param Uuid|null $correlationId
     */
    public function __construct(
        private readonly Uuid $id,
        private readonly TenantId $tenantId,
        private readonly Uuid $restaurantId,
        private readonly Uuid $clientId,
        public Collection $items,
        public Money $totalPrice,
        public OrderStatus $status,
        private ?Carbon $createdAt = null,
        private readonly ?Carbon $updatedAt = null,
        private readonly ?Uuid $correlationId = null
    ) {
        parent::__construct($id);
        $this->createdAt ??= Carbon::now();
        $this->updatedAt ??= Carbon::now();
    }

    public static function place(
        Uuid $id,
        TenantId $tenantId,
        Uuid $restaurantId,
        Uuid $clientId,
        Collection $items,
        ?Uuid $correlationId = null
    ): self {
        $totalPrice = self::calculateTotal($items);
        $order = new self(
            id: $id,
            tenantId: $tenantId,
            restaurantId: $restaurantId,
            clientId: $clientId,
            items: $items,
            totalPrice: $totalPrice,
            status: OrderStatus::PENDING,
            correlationId: $correlationId
        );

        $order->record(new OrderWasPlaced($order->id, $order->clientId, $order->totalPrice, $correlationId));

        return $order;
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::PENDING) {
            // throw new \DomainException('Only pending orders can be confirmed.');
        }
        $this->status = OrderStatus::CONFIRMED;
        $this->updatedAt = Carbon::now();
    }

    public function markAsReadyForPickup(): void
    {
        if ($this->status !== OrderStatus::CONFIRMED) {
            // throw new \DomainException('Only confirmed orders can be marked as ready.');
        }
        $this->status = OrderStatus::READY_FOR_PICKUP;
        $this->updatedAt = Carbon::now();
        $this->record(new OrderReadyForPickup($this->id, $this->clientId, $this->correlationId));
    }

    public function complete(): void
    {
        if ($this->status !== OrderStatus::READY_FOR_PICKUP) {
            // throw new \DomainException('Only orders ready for pickup can be completed.');
        }
        $this->status = OrderStatus::COMPLETED;
        $this->updatedAt = Carbon::now();
    }

    public function cancel(string $reason): void
    {
        if ($this->status->isFinal()) {
            // throw new \DomainException('Cannot cancel a completed or already cancelled order.');
        }
        $this->status = OrderStatus::CANCELLED;
        $this->updatedAt = Carbon::now();
        // Optionally, record a cancellation event with the reason
    }

    private static function calculateTotal(Collection $items): Money
    {
        $total = $items->reduce(function ($carry, OrderItem $item) {
            return $carry + $item->getTotalPrice()->getAmount();
        }, 0);

        return new Money($total);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'tenant_id' => $this->tenantId->toString(),
            'restaurant_id' => $this->restaurantId->toString(),
            'client_id' => $this->clientId->toString(),
            'items' => $this->items->map(fn (OrderItem $item) => $item->toArray())->all(),
            'total_price' => $this->totalPrice->toArray(),
            'status' => $this->status->value,
            'created_at' => $this->createdAt?->toIso8601String(),
            'updated_at' => $this->updatedAt?->toIso8601String(),
            'correlation_id' => $this->correlationId?->toString(),
        ];
    }
}
