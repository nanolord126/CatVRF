<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Domain\Events;

use App\Domains\Hotels\Domain\ValueObjects\BookingId;
use App\Shared\Domain\Events\DomainEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class BookingConfirmed
 *
 * Part of the Hotels vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\Hotels\Domain\Events
 */
final class BookingConfirmed extends DomainEvent
{
    use \Illuminate\Foundation\Events\Dispatchable, \Illuminate\Queue\SerializesModels;

    /**
     * @param BookingId $bookingId
     * @param int $totalPrice
     * @param int $userId
     * @param string $correlationId
     */
    public function __construct(
        private readonly BookingId $bookingId,
        private readonly int $totalPrice,
        private readonly int $userId,
        string $correlationId
    ) {
        parent::__construct($correlationId);

        if ($totalPrice < 0) {
            throw new \InvalidArgumentException('Total price cannot be negative');
        }

        if (trim($correlationId) === '') {
            throw new \InvalidArgumentException('Correlation ID is required for booking events');
        }
    }

    public function getEventName(): string
    {
        return 'hotels.booking.confirmed';
    }

    public function getPayload(): array
    {
        return [
            'booking_id' => $this->bookingId->toString(),
            'total_price' => $this->totalPrice,
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
        ];
    }

    public function getCorrelationId(): string
    {
        return parent::getCorrelationId();
    }
}

