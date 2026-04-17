<?php declare(strict_types=1);

namespace App\Domains\Electronics\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class EscrowReleasedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public object $escrowHold,
        public string $reason,
        public string $correlationId,
    ) {
    }
}
