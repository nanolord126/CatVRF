<?php

declare(strict_types=1);

namespace App\Domains\Sports\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FraudDetectedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $fraudType,
        public readonly float $riskScore,
        public readonly array $fraudDetails,
        public readonly string $correlationId,
    ) {}

    public function broadcastOn(): array
    {
        return [];
    }
}
