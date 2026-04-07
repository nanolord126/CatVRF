<?php

declare(strict_types=1);

namespace App\Domains\Food\Domain\Events;

use App\Shared\Domain\Events\DomainEvent;
use App\Shared\Domain\ValueObjects\Money;
use App\Shared\Domain\ValueObjects\Uuid;

/**
 * Class OrderWasPlaced
 *
 * Part of the Food vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\Food\Domain\Events
 */
final class OrderWasPlaced extends DomainEvent
{
    public function __construct(
        public readonly Uuid $orderId,
        public readonly Uuid $clientId,
        public readonly Money $totalPrice,
        private ?Uuid $correlationId = null
    ) {
        parent::__construct($correlationId);
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId->toString(),
            'client_id' => $this->clientId->toString(),
            'total_price' => $this->totalPrice->toArray(),
            'correlation_id' => $this->correlationId?->toString(),
            'occurred_on' => $this->occurredOn(),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            orderId: new Uuid($data['order_id']),
            clientId: new Uuid($data['client_id']),
            totalPrice: new Money($data['total_price']['amount'], $data['total_price']['currency']),
            correlationId: isset($data['correlation_id']) ? new Uuid($data['correlation_id']) : null
        );
    }

    public function eventName(): string
    {
        return 'food.order.placed';
    }
}
