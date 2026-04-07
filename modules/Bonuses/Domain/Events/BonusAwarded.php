<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

final class BonusAwarded implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public string $queue = 'bonuses';

    public function __construct(
        public readonly int $bonusId,
        public readonly int $userId,
        public readonly int $amount,
        public readonly string $correlationId
    ) {
    }

    public function broadcastOn(): array
    {
        return [new \Illuminate\Broadcasting\PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'bonus.awarded';
    }
}
