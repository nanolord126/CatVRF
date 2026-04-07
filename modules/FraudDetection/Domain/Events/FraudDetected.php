<?php

declare(strict_types=1);

namespace Modules\FraudDetection\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

final class FraudDetected implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public string $queue = 'fraud-alerts';

    public function __construct(
        public readonly string $transactionId,
        public readonly int $userId,
        public readonly float $score,
        public readonly string $correlationId
    ) {
    }

    public function broadcastOn(): array
    {
        // Отправляем событие в приватный канал администраторов
        return [new \Illuminate\Broadcasting\PrivateChannel('admin.fraud-alerts')];
    }

    public function broadcastAs(): string
    {
        return 'fraud.detected';
    }
}
