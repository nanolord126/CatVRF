<?php declare(strict_types=1);

namespace App\Domains\Food\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $orderId,
        public readonly int $restaurantId,
        public readonly int $clientId,
        public readonly int $totalAmount,
        public readonly string $correlationId,
    ) {}
}
