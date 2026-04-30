<?php

declare(strict_types=1);

namespace App\Domains\Sports\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LiveStreamStartedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $streamId,
        public readonly int $trainerId,
        public readonly string $streamTitle,
        public readonly string $webrtcRoom,
        public readonly string $correlationId,
    ) {}

    public function broadcastOn(): array
    {
        return [];
    }
}
