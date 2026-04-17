<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class VideoCallEndedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $callId,
        public int $userId,
        public int $masterId,
        public int $durationSeconds,
        public string $reason,
        public string $correlationId,
    ) {}
}
