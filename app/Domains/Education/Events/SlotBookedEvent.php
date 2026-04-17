<?php declare(strict_types=1);

namespace App\Domains\Education\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SlotBookedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $bookingId,
        public readonly string $bookingReference,
        public readonly int $slotId,
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly ?int $businessGroupId,
        public readonly string $correlationId,
    ) {}
}
