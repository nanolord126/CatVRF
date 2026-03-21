<?php declare(strict_types=1);

namespace App\Domains\Hotels\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CheckoutCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $bookingId,
        public readonly int $hotelId,
        public readonly int $clientId,
        public readonly int $totalAmount,
        public readonly string $correlationId,
    ) {}
}
