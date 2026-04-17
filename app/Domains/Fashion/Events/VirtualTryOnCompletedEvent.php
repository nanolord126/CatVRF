<?php declare(strict_types=1);

namespace App\Domains\Fashion\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class VirtualTryOnCompletedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $designId,
        public int $userId,
        public int $tenantId,
        public ?int $businessGroupId,
        public array $tryOnResults,
        public float $averageFitScore,
        public string $correlationId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('fashion.' . $this->userId),
            new PrivateChannel('tenant.' . $this->tenantId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'fashion.virtual.try.on.completed';
    }
}
