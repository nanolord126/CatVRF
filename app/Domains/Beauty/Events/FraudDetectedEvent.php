<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class FraudDetectedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public float $fraudScore,
        public string $riskLevel,
        public string $action,
        public string $correlationId,
    ) {}
}
